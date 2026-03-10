<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PriceType;
use App\Models\Sale;
use Carbon\Carbon;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;

class SaleForm
{
    protected static array $customerPriceTypeCache = [];
    protected static array $productPriceCache = [];
    protected static ?int $defaultPriceTypeId = null;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
            Section::make('Data Penjualan')
                ->columnSpanFull()
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes(self::enterKeyGuardAttributes())
                ->columns(12)
                ->schema([
                    TextInput::make('number')
                        ->label('No Nota')
                        ->readOnly()
                        ->dehydrated()
                        ->default(fn (): ?string => self::generateNumber(now()->toDateString()))
                        ->helperText('Otomatis saat simpan.')
                        ->maxLength(50)
                        ->columnSpan(3),
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state): void {
                            $details = $get('details') ?? [];

                            if (! is_array($details)) {
                                return;
                            }

                            foreach ($details as $key => $detail) {
                                $productId = (int) ($detail['product_id'] ?? 0);

                                if ($productId <= 0) {
                                    continue;
                                }

                                $currentPrice = self::parseThousandNumber($detail['price'] ?? 0);

                                if ($currentPrice > 0) {
                                    continue;
                                }

                                $details[$key]['price'] = self::resolveDefaultProductPrice($productId, $state);
                            }

                            $set('details', $details);
                        })
                        ->searchable()
                        ->preload()
                        ->columnSpan(4),

                    Textarea::make('note')
                        ->label('Catatan')
                        ->placeholder('Catatan singkat (opsional)')
                        ->rows(2)
                        ->maxLength(150)
                        ->columnSpan(5),
                   // TextInput::make('reference_number')->maxLength(100),

                ]),

            Section::make()
                ->columnSpanFull()
                ->extraAttributes(self::enterKeyGuardAttributes())
                ->schema([
                    Grid::make(12)
                        ->schema([
                            TextInput::make('barcode_scan')
                                ->hiddenLabel()
                                ->placeholder('Scan barcode, lalu Enter')
                                ->dehydrated(false)
                                ->live(onBlur: true)
                                ->maxWidth(Width::ExtraSmall)
                                ->extraInputAttributes([
                                    'autocomplete' => 'off',
                                    'autocorrect' => 'off',
                                    'autocapitalize' => 'off',
                                    'spellcheck' => 'false',
                                    'style' => 'height: 4.8rem; font-size: 1.5rem;',
                                    'x-on:keydown.enter.stop.prevent' => "
                                        if (! \$el.value.trim()) return;
                                        \$el.dispatchEvent(new Event('change', { bubbles: true }));
                                        \$el.blur();
                                    ",
                                    'x-data' => '{}',
                                    'x-init' => '$nextTick(() => $el.focus())',                                    
                                    'x-on:sale-focus-barcode.window' => '$nextTick(() => $el.focus())',
                                ])
                                ->afterStateUpdated(function ($state, Set $set, Get $get, LivewireComponent $livewire): void {
                                    $barcode = trim((string) $state);

                                    if ($barcode === '') {
                                        return;
                                    }

                                    $product = Product::query()
                                        ->select(['id', 'code', 'name'])
                                        ->where('code', $barcode)
                                        ->first();

                                    if (! $product) {
                                        Notification::make()
                                            ->title("Barcode '{$barcode}' tidak ditemukan.")
                                            ->danger()
                                            ->send();

                                        $set('barcode_scan', null);
                                        $livewire->dispatch('sale-focus-barcode');

                                        return;
                                    }

                                    $set(
                                        'details',
                                        self::appendScannedProductToDetails(
                                            $get('details') ?? [],
                                            (int) $product->id,
                                            $get('customer_id'),
                                        ),
                                    );

                                    $set('barcode_scan', null);
                                    $livewire->dispatch('sale-focus-barcode');
                                })
                                ->columnSpan(['default' => 12, 'lg' => 8]),
                            TextEntry::make('grand_total_preview')
                                ->hiddenLabel()
                                ->state(function (Get $get): string {
                                    $detailsSubtotal = self::calculateDetailsSubtotal($get('details') ?? []);

                                    $grandTotal = self::calculateGrandTotal(
                                        $detailsSubtotal,
                                        $get('discount_percent'),
                                        $get('discount_amount'),
                                        $get('ppn'),
                                        $get('pph'),
                                    );

                                    return number_format($grandTotal, 0, ',', '.');
                                })
                                ->formatStateUsing(fn ($state): HtmlString => new HtmlString(
                                    '<div style="display:flex; justify-content:flex-end; margin-bottom:.25rem;">
                                        <div style="
                                            min-width: 24rem;
                                            width: fit-content;
                                            height: 4.8rem;
                                            display: flex;
                                            align-items: center;
                                            justify-content: flex-end;
                                            text-align: right;
                                            padding: .9rem 1.25rem;
                                            border-radius: .75rem;
                                            border: 1px solid rgba(17, 24, 39, .12);
                                            background: #f8fafc;
                                            font-size: 2.4rem;
                                            line-height: 1;
                                            font-weight: 800;
                                            letter-spacing: .02em;
                                            font-variant-numeric: tabular-nums;
                                        ">' . e((string) $state) . '</div>
                                    </div>'
                                ))
                                ->html()
                                ->columnSpan(['default' => 12, 'lg' => 4]),
                        ]),
                    Repeater::make('details')
                        ->relationship('details')
                        ->defaultItems(1)
                        ->table([
                            TableColumn::make('No')
                                ->width('56px')
                                ->alignment('center'),
                            TableColumn::make('Product')
                                ->markAsRequired(),
                            TableColumn::make('Qty')
                                ->markAsRequired()
                                ->alignRight()
                                ->width('100px'),
                            TableColumn::make('Price')
                                ->markAsRequired()
                                ->alignRight()
                                ->width('150px'),
                            TableColumn::make('Disc %')
                                ->alignRight()
                                ->width('100px'),
                            TableColumn::make('Disc Rp')
                                ->alignRight()
                                ->width('150px'),
                            TableColumn::make('Total')
                                ->alignRight()
                                ->width('120px'),
                        ])
                        ->schema([
                            TextEntry::make('line_number')
                                ->label('No')
                                ->state(fn (int $parentRepeaterItemIndex): string => (string) ($parentRepeaterItemIndex + 1)),

                            Select::make('product_id')
                                ->label('Product')
                                ->relationship(
                                    'product',
                                    'name',
                                    modifyQueryUsing: fn (Builder $query) => $query
                                        ->with(['brand:id,name', 'productCategory:id,name', 'unit:id,name'])
                                        ->orderBy('name'),
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn (Product $record): string => self::formatProductOptionHtml($record)
                                )
                                ->getOptionLabelUsing(function (Select $component): ?string {
                                    $record = $component->getSelectedRecord();

                                    if (! $record instanceof Product) {
                                        return null;
                                    }

                                    return self::formatSelectedProductLabel($record);
                                })
                                ->searchable(['code', 'name'])
                                ->searchDebounce(150)
                                ->optionsLimit(20)
                                ->getSearchResultsUsing(function (?string $search): array {
                                    $search = trim((string) $search);

                                    if ($search === '') {
                                        return [];
                                    }

                                    $query = Product::query()
                                        ->with(['brand:id,name', 'productCategory:id,name', 'unit:id,name'])
                                        ->where(function (Builder $query) use ($search): void {
                                            $query
                                                ->where('code', $search)
                                                ->orWhere('code', 'like', "{$search}%")
                                                ->orWhere('name', 'like', "%{$search}%");
                                        })
                                        ->orderByRaw(
                                            'case when code = ? then 0 when code like ? then 1 else 2 end',
                                            [$search, "{$search}%"],
                                        )
                                        ->orderBy('name')
                                        ->limit(20);

                                    return $query
                                        ->get()
                                        ->mapWithKeys(fn (Product $product): array => [
                                            $product->getKey() => self::formatProductOptionHtml($product),
                                        ])
                                        ->all();
                                })
                                ->allowHtml()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                    $productId = (int) $state;

                                    if ($productId <= 0) {
                                        return;
                                    }

                                    $currentPrice = self::parseThousandNumber($get('price') ?? 0);

                                    if ($currentPrice > 0) {
                                        return;
                                    }

                                    $customerId = $get('../../customer_id') ?? $get('customer_id');

                                    $set('price', self::resolveDefaultProductPrice($productId, $customerId));
                                })
                                ->required()
                                ->hiddenLabel(),

                            TextInput::make('qty')
                                ->label('Qty')
                                ->type('text')
                                ->mask(self::thousandMask())
                                ->formatStateUsing(fn ($state) => self::formatThousandInput($state))
                                ->dehydrateStateUsing(fn ($state) => self::dehydrateMaskedNumber($state))
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->hiddenLabel(),

                            TextInput::make('price')
                                ->label('Price')
                                ->type('text')
                                ->mask(self::thousandMask())
                                ->formatStateUsing(fn ($state) => self::formatThousandInput($state))
                                ->dehydrateStateUsing(fn ($state) => self::dehydrateMaskedNumber($state))
                                ->default(0)
                                ->required()
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->hiddenLabel(),

                            TextInput::make('discount_percent')
                                ->label('Disc %')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->hiddenLabel(),

                            TextInput::make('discount_amount')
                                ->label('Disc Rp')
                                ->type('text')
                                ->mask(self::thousandMask())
                                ->formatStateUsing(fn ($state) => self::formatThousandInput($state))
                                ->dehydrateStateUsing(fn ($state) => self::dehydrateMaskedNumber($state))
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->hiddenLabel(),

                            TextEntry::make('line_total')
                                ->label('Total')
                                ->state(function (Get $get) {
                                    $lineTotal = self::calculateLineTotal(
                                        $get('qty'),
                                        $get('price'),
                                        $get('discount_percent'),
                                        $get('discount_amount'),
                                    );

                                    return number_format($lineTotal, 0, ',', '.');
                                })
                                ->alignEnd()
                                ->extraAttributes(['style' => 'font-variant-numeric: tabular-nums;'])
                                ->hiddenLabel(),
                        ]),
                    TextEntry::make('details_subtotal_row')
                        ->hiddenLabel()
                        ->state(function (Get $get): string {
                            $subtotal = self::calculateDetailsSubtotal($get('details') ?? []);

                            return number_format($subtotal, 0, ',', '.');
                        })
                        ->formatStateUsing(fn ($state): HtmlString => new HtmlString(
                            '<div style="display: grid; grid-template-columns: 56px 1fr 100px 150px 100px 150px 120px 48px; align-items: center; column-gap: 0;">
                                <div style="grid-column: 1 / 7; text-align: right; font-weight: 500;">Subtotal</div>
                                <div style="grid-column: 7; text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;">' . e((string) $state) . '</div>
                            </div>'
                        ))
                        ->html(),
                ]),
            ]);
    }

    public static function generateNumber(mixed $saleDate = null): ?string
    {
        $date = blank($saleDate)
            ? now()
            : Carbon::parse((string) $saleDate);

        $receiptHeader = trim((string) config('app.receipt_header', env('RECEIPT_HEADER', '')), " \t\n\r\0\x0B/");
        $datePart = $date->format('Y/m/d');
        $prefix = filled($receiptHeader)
            ? "{$receiptHeader}/{$datePart}/"
            : "{$datePart}/";
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d{4})$/';

        $usedNumbers = Sale::query()
            ->withTrashed()
            ->where('number', 'like', "{$prefix}%")
            ->pluck('number')
            ->map(function (string $number) use ($pattern): ?int {
                $matches = [];

                if (! preg_match($pattern, $number, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter(fn (?int $value): bool => $value !== null)
            ->flip();

        for ($increment = 1; $increment <= 9999; $increment++) {
            if (! $usedNumbers->has($increment)) {
                return $prefix . str_pad((string) $increment, 4, '0', STR_PAD_LEFT);
            }
        }

        return null;
    }

    protected static function resolveDefaultProductPrice(int $productId, mixed $customerId = null): float
    {
        if ($productId <= 0) {
            return 0;
        }

        $priceTypeId = self::resolvePriceTypeIdForCustomer($customerId);

        if ($priceTypeId !== null) {
            $price = self::findProductPrice($productId, $priceTypeId);

            if ($price !== null) {
                return $price;
            }
        }

        $fallbackPriceTypeId = self::getDefaultPriceTypeId();

        if ($fallbackPriceTypeId !== null && $fallbackPriceTypeId !== $priceTypeId) {
            $price = self::findProductPrice($productId, $fallbackPriceTypeId);

            if ($price !== null) {
                return $price;
            }
        }

        return 0;
    }

    protected static function resolvePriceTypeIdForCustomer(mixed $customerId): ?int
    {
        if (blank($customerId)) {
            return self::getDefaultPriceTypeId();
        }

        $customerId = (int) $customerId;

        if ($customerId <= 0) {
            return self::getDefaultPriceTypeId();
        }

        if (array_key_exists($customerId, self::$customerPriceTypeCache)) {
            return self::$customerPriceTypeCache[$customerId];
        }

        $priceTypeId = Customer::query()
            ->whereKey($customerId)
            ->value('price_type_id');

        if (filled($priceTypeId)) {
            return self::$customerPriceTypeCache[$customerId] = (int) $priceTypeId;
        }

        return self::$customerPriceTypeCache[$customerId] = self::getDefaultPriceTypeId();
    }

    protected static function getDefaultPriceTypeId(): ?int
    {
        if (self::$defaultPriceTypeId !== null) {
            return self::$defaultPriceTypeId > 0 ? self::$defaultPriceTypeId : null;
        }

        $priceTypeId = PriceType::query()
            ->where('index', 1)
            ->value('id');

        if (blank($priceTypeId)) {
            $priceTypeId = PriceType::query()
                ->orderBy('index')
                ->value('id');
        }

        self::$defaultPriceTypeId = (int) ($priceTypeId ?? 0);

        return self::$defaultPriceTypeId > 0 ? self::$defaultPriceTypeId : null;
    }

    protected static function findProductPrice(int $productId, int $priceTypeId): ?float
    {
        $cacheKey = "{$productId}:{$priceTypeId}";

        if (array_key_exists($cacheKey, self::$productPriceCache)) {
            return self::$productPriceCache[$cacheKey];
        }

        $price = ProductPrice::query()
            ->where('product_id', $productId)
            ->where('price_type_id', $priceTypeId)
            ->value('price');

        if ($price === null) {
            self::$productPriceCache[$cacheKey] = null;

            return null;
        }

        return self::$productPriceCache[$cacheKey] = round((float) $price, 2);
    }

    protected static function formatProductOptionHtml(Product $product): string
    {
        $mainLabel = filled($product->code)
            ? "[{$product->code}] {$product->name}"
            : (string) $product->name;

        $metaRowOne = implode(' | ', array_filter([
            filled($product->brand?->name) ? "Brand: {$product->brand->name}" : null,
            filled($product->productCategory?->name) ? "Kategori: {$product->productCategory->name}" : null,
            filled($product->unit?->name) ? "Satuan: {$product->unit->name}" : null,
            filled($product->type) ? "Tipe: {$product->type}" : null,
        ]));

        $metaRowTwo = implode(' | ', array_filter([
            filled($product->location) ? 'Lokasi: ' . Str::limit((string) $product->location, 40) : null,
            filled($product->description) ? 'Deskripsi: ' . Str::limit((string) $product->description, 70) : null,
        ]));

        $html = '<div class="fi-text-sm fi-font-medium">' . e($mainLabel) . '</div>';

        if (filled($metaRowOne)) {
            $html .= '<div class="fi-text-xs fi-text-gray-500">' . e($metaRowOne) . '</div>';
        }

        if (filled($metaRowTwo)) {
            $html .= '<div class="fi-text-xs fi-text-gray-500">' . e($metaRowTwo) . '</div>';
        }

        return $html;
    }

    protected static function formatSelectedProductLabel(Product $product): string
    {
        return filled($product->code)
            ? "[{$product->code}] {$product->name}"
            : (string) $product->name;
    }

    protected static function enterKeyGuardAttributes(): array
    {
        return [
            'x-on:keydown.enter' => "
                if (\$event.target.tagName === 'TEXTAREA' || \$event.target.closest('.fi-select-input-dropdown')) {
                    return;
                }

                \$event.preventDefault();

                if (\$event.target.matches('input, textarea')) {
                    \$event.target.dispatchEvent(new Event('change', { bubbles: true }));
                    \$event.target.blur();
                }
            ",
        ];
    }

    protected static function calculateLineTotal(
        mixed $qty,
        mixed $price,
        mixed $discountPercent = 0,
        mixed $discountAmount = 0,
    ): float {
        $qty = self::parseThousandNumber($qty);
        $price = self::parseThousandNumber($price);
        $discountPercent = self::parseNumber($discountPercent);
        $discountAmount = self::parseThousandNumber($discountAmount);

        $gross = max(0, $qty) * max(0, $price);
        $discountPercentAmount = $gross * max(0, $discountPercent) / 100;
        $net = $gross - $discountPercentAmount - max(0, $discountAmount);

        return max(0, $net);
    }

    protected static function calculateDetailsSubtotal(array $rows): float
    {
        $subtotal = 0;

        foreach ($rows as $row) {
            $subtotal += self::calculateLineTotal(
                $row['qty'] ?? 0,
                $row['price'] ?? 0,
                $row['discount_percent'] ?? 0,
                $row['discount_amount'] ?? 0,
            );
        }

        return max(0, $subtotal);
    }

    protected static function calculateGrandTotal(
        mixed $detailsSubtotal,
        mixed $headerDiscountPercent,
        mixed $headerDiscountAmount,
        mixed $ppnPercent,
        mixed $pphPercent,
    ): float {
        $detailsSubtotal = self::parseNumber($detailsSubtotal);
        $headerDiscountPercent = self::parseNumber($headerDiscountPercent);
        $headerDiscountAmount = self::parseThousandNumber($headerDiscountAmount);
        $ppnPercent = self::parseNumber($ppnPercent);
        $pphPercent = self::parseNumber($pphPercent);

        $headerDiscPercentAmount = $detailsSubtotal * max(0, $headerDiscountPercent) / 100;
        $afterHeaderDiscount = $detailsSubtotal - $headerDiscPercentAmount - max(0, $headerDiscountAmount);
        $afterHeaderDiscount = max(0, $afterHeaderDiscount);

        $ppnAmount = $afterHeaderDiscount * max(0, $ppnPercent) / 100;
        $pphAmount = $afterHeaderDiscount * max(0, $pphPercent) / 100;

        return max(0, $afterHeaderDiscount + $ppnAmount + $pphAmount);
    }

    protected static function numericInputAttributes(): array
    {
        return [
            'class' => 'text-right',
            'style' => 'text-align: right;',
            'inputmode' => 'numeric',
        ];
    }

    protected static function thousandMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            $money($input, ',', '.', 0)
        JS);
    }

    protected static function dehydrateMaskedNumber(mixed $state): int
    {
        return (int) round(self::parseLocalizedNumber($state));
    }

    protected static function formatThousandInput(mixed $state): string
    {
        return number_format(self::parseLocalizedNumber($state), 0, ',', '.');
    }

    protected static function parseNumber(mixed $value): float
    {
        return self::parseLocalizedNumber($value);
    }

    protected static function parseThousandNumber(mixed $value): float
    {
        return self::parseLocalizedNumber($value);
    }

    protected static function parseLocalizedNumber(mixed $value): float
    {
        if (blank($value)) {
            return 0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = trim((string) $value);

        if ($normalized === '' || $normalized === '-') {
            return 0;
        }

        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (str_contains($normalized, '.') && str_contains($normalized, ',')) {
            $lastDot = strrpos($normalized, '.');
            $lastComma = strrpos($normalized, ',');

            if ($lastDot !== false && $lastComma !== false && $lastDot > $lastComma) {
                // Example: 200,000.50
                $normalized = str_replace(',', '', $normalized);
            } else {
                // Example: 200.000,50
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif (str_contains($normalized, '.')) {
            if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $normalized)) {
                // Example: 200.000
                $normalized = str_replace('.', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $normalized)) {
                // Example: 200,000
                $normalized = str_replace(',', '', $normalized);
            } else {
                // Example: 200000,50
                $normalized = str_replace(',', '.', $normalized);
            }
        }

        $normalized = preg_replace('/[^\d.-]/', '', $normalized);

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === '-.') {
            return 0;
        }

        return (float) $normalized;
    }

    protected static function appendScannedProductToDetails(
        array $details,
        int $productId,
        mixed $customerId = null,
    ): array
    {
        $defaultPrice = self::resolveDefaultProductPrice($productId, $customerId);

        foreach ($details as $key => $detail) {
            if ((int) ($detail['product_id'] ?? 0) === $productId) {
                $currentQty = self::parseThousandNumber($detail['qty'] ?? 0);
                $details[$key]['qty'] = max(0, $currentQty) + 1;

                if (self::parseThousandNumber($detail['price'] ?? 0) <= 0) {
                    $details[$key]['price'] = $defaultPrice;
                }

                return $details;
            }
        }

        foreach ($details as $key => $detail) {
            if (blank($detail['product_id'] ?? null)) {
                $details[$key]['product_id'] = $productId;
                $details[$key]['qty'] = max(1, self::parseThousandNumber($detail['qty'] ?? 1));
                $details[$key]['price'] = $defaultPrice;
                $details[$key]['discount_percent'] = self::parseNumber($detail['discount_percent'] ?? 0);
                $details[$key]['discount_amount'] = self::parseThousandNumber($detail['discount_amount'] ?? 0);

                return $details;
            }
        }

        $details[] = [
            'product_id' => $productId,
            'qty' => 1,
            'price' => $defaultPrice,
            'discount_percent' => 0,
            'discount_amount' => 0,
        ];

        return $details;
    }
}
