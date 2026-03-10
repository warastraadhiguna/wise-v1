<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PriceType;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('product_category_id')
                            ->label('Kategori Produk')
                            ->relationship('productCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->maxLength(20)
                            ->suffixAction(
                                Action::make('generateCode')
                                    ->label('Generate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->action(function ($get, $set): void {
                                        $brandId = $get('brand_id');
                                        $productCategoryId = $get('product_category_id');
                                        $unitId = $get('unit_id');

                                        if (blank($brandId) || blank($productCategoryId) || blank($unitId)) {
                                            Notification::make()
                                                ->title('Brand, kategori produk, dan satuan wajib diisi dulu.')
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        $generatedCode = self::generateCode(
                                            (int) $brandId,
                                            (int) $productCategoryId,
                                            (int) $unitId,
                                        );

                                        if (blank($generatedCode)) {
                                            Notification::make()
                                                ->title('Kode otomatis tidak tersedia (range 00001-99999 sudah habis).')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $set('code', $generatedCode);
                                    }),
                                true,
                            )
                            ->helperText('Jika dikosongkan, klik Generate untuk membuat kode otomatis.')
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(200),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([                    
                    Textarea::make('location')
                        ->label('Lokasi')
                        ->rows(2),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('unit_notes')
                            ->label('Catatan Satuan')
                            ->rows(2),
                        Textarea::make('price_notes')
                            ->label('Catatan Harga')
                            ->rows(2),
                    ]),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema(self::buildPriceTypeFields()),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                    TextInput::make('type')
                        ->label('Tipe')
                        ->maxLength(500),                        
                        TextInput::make('minimum_stock')
                            ->label('Minimum Stok')
                            ->numeric()
                            ->step('0.01')
                            ->rule('decimal:0,2')
                            ->default(0)
                            ->required(),
                        Toggle::make('input_status')
                            ->label('Input Status')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function generateCode(int $brandId, int $productCategoryId, int $unitId): ?string
    {
        $prefix = "{$brandId}{$productCategoryId}{$unitId}";
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d{5})$/';

        $usedNumbers = Product::query()
            ->withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->pluck('code')
            ->map(function (string $code) use ($pattern): ?int {
                $matches = [];

                if (! preg_match($pattern, $code, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->flip();

        for ($increment = 1; $increment <= 99999; $increment++) {
            if (! $usedNumbers->has($increment)) {
                return $prefix . str_pad((string) $increment, 5, '0', STR_PAD_LEFT);
            }
        }

        return null;
    }

    public static function buildPriceMapFromProduct(Product $product): array
    {
        $existing = $product->prices()
            ->pluck('price', 'price_type_id')
            ->map(fn (mixed $value): float => round((float) $value, 2))
            ->all();

        $priceMap = [];

        foreach (self::getPriceTypes() as $priceType) {
            $priceTypeId = (int) $priceType->id;
            $priceMap[$priceTypeId] = (float) ($existing[$priceTypeId] ?? 0);
        }

        return $priceMap;
    }

    public static function normalizePriceMap(array $priceMap): array
    {
        $normalizedInput = collect($priceMap)
            ->mapWithKeys(function (mixed $price, mixed $priceTypeId): array {
                $id = (int) $priceTypeId;

                if ($id <= 0) {
                    return [];
                }

                return [$id => self::normalizePriceValue($price)];
            })
            ->all();

        $normalized = [];

        foreach (self::getPriceTypes() as $priceType) {
            $priceTypeId = (int) $priceType->id;
            $normalized[$priceTypeId] = (float) ($normalizedInput[$priceTypeId] ?? 0);
        }

        return $normalized;
    }

    public static function syncPrices(Product $product, array $priceMap): void
    {
        $normalized = self::normalizePriceMap($priceMap);
        $now = now();
        $upserts = [];

        foreach ($normalized as $priceTypeId => $price) {
            $upserts[] = [
                'product_id' => $product->getKey(),
                'price_type_id' => (int) $priceTypeId,
                'price' => $price,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($upserts === []) {
            return;
        }

        ProductPrice::query()->upsert(
            $upserts,
            ['product_id', 'price_type_id'],
            ['price', 'updated_at'],
        );
    }

    public static function buildPriceTypeFields(): array
    {
        return self::getPriceTypes()
            ->map(function (PriceType $priceType): TextInput {
                return TextInput::make('price_map.' . $priceType->id)
                    ->label($priceType->name)
                    ->numeric()
                    ->prefix('Rp')
                    ->step('0.01')
                    ->rule('decimal:0,2')
                    ->default(0)
                    ->minValue(0)
                    ->required();
            })
            ->all();
    }

    protected static function getPriceTypes(): Collection
    {
        return PriceType::query()
            ->orderBy('index')
            ->get(['id', 'name']);
    }

    protected static function normalizePriceValue(mixed $value): float
    {
        if (blank($value)) {
            return 0;
        }

        return max(0, round((float) $value, 2));
    }
}
