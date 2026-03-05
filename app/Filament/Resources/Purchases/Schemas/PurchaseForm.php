<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Models\PaymentMethod;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PurchaseForm
{
    protected static array $paymentMethodCashCache = [];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
            Section::make('Data Pembelian')
                ->columnSpanFull()
                ->collapsible()
                ->persistCollapsed()
                ->extraAttributes(self::enterKeyGuardAttributes())
                ->columns(12)
                ->schema([
                    TextInput::make('number')
                        ->label('No Nota')
                        ->required()
                        ->maxLength(50)
                        ->columnSpan(5),
                        // ->default(fn () => 'PO-' . now()->format('Ymd-His')),
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(4),

                    DatePicker::make('purchase_date')
                        ->label('Tanggal Pembelian')
                        ->required()
                        ->default(now())
                        ->columnSpan(3),
                    Select::make('payment_method_id')
                        ->label('Cara Bayar')
                        ->relationship(
                            'paymentMethod',
                            'name',
                            modifyQueryUsing: fn (Builder $query) => $query->orderBy('index'),
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state): void {
                            if (self::isCashPaymentMethod($state)) {
                                $set('due_date', null);
                            }
                        })
                        ->columnSpan(3),

                    DatePicker::make('due_date')                    
                        ->label('Jatuh Tempo')
                        ->visible(fn (Get $get): bool => ! self::isCashPaymentMethod($get('payment_method_id')))
                        ->required(fn (Get $get): bool => ! self::isCashPaymentMethod($get('payment_method_id')))
                        ->dehydrateStateUsing(fn ($state, Get $get) => self::isCashPaymentMethod($get('payment_method_id')) ? null : $state)
                        ->columnSpan(3),
                    Textarea::make('note')
                        ->rows(2)
                        ->columnSpan(fn (Get $get): int => self::isCashPaymentMethod($get('payment_method_id')) ? 9 : 6),
                    Grid::make(12)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('discount_percent')
                                ->label('Disc %')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->columnSpan(2),
                            TextInput::make('discount_amount')
                                ->label('Disc Rp')
                                ->type('text')
                                ->mask(self::thousandMask())
                                ->formatStateUsing(fn ($state) => self::formatThousandInput($state))
                                ->dehydrateStateUsing(fn ($state) => self::dehydrateMaskedNumber($state))
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->columnSpan(2),
                            TextInput::make('ppn')
                                ->label('PPN')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->columnSpan(2),
                            TextInput::make('pph')
                                ->label('PPH')
                                ->numeric()
                                ->default(0)
                                ->live(onBlur: true)
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->columnSpan(2),
                            TextInput::make('payment_amount')
                                ->label(fn (Get $get): string => self::isCashPaymentMethod($get('payment_method_id')) ? 'Jumlah Bayar' : 'Uang Muka')
                                ->type('text')
                                ->mask(RawJs::make(<<<'JS'
                                    $money($input, ',', '.', 0)
                                JS))
                                ->formatStateUsing(fn ($state) => self::formatThousandInput($state))
                                ->extraInputAttributes(self::numericInputAttributes())
                                ->dehydrateStateUsing(fn ($state) => self::dehydrateMaskedNumber($state))
                                ->default(0)
                                ->columnSpan(4),
                        ]),
                   // TextInput::make('reference_number')->maxLength(100),

                ]),

            Section::make()
                ->columnSpanFull()
                ->extraAttributes(self::enterKeyGuardAttributes())
                ->schema([
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
                                    min-width: 20rem;
                                    width: fit-content;
                                    text-align: right;
                                    padding: .9rem 1.1rem;
                                    border-radius: .75rem;
                                    border: 1px solid rgba(17, 24, 39, .12);
                                    background: #f8fafc;
                                    font-size: 2.25rem;
                                    line-height: 1;
                                    font-weight: 800;
                                    letter-spacing: .02em;
                                    font-variant-numeric: tabular-nums;
                                ">' . e((string) $state) . '</div>
                            </div>'
                        ))
                        ->html(),
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

    protected static function isCashPaymentMethod($paymentMethodId): bool
    {
        if (blank($paymentMethodId)) {
            return true;
        }

        $paymentMethodId = (int) $paymentMethodId;

        if (array_key_exists($paymentMethodId, self::$paymentMethodCashCache)) {
            return self::$paymentMethodCashCache[$paymentMethodId];
        }

        $value = PaymentMethod::query()
            ->whereKey($paymentMethodId)
            ->value('is_cash');

        return self::$paymentMethodCashCache[$paymentMethodId] = $value === null ? true : (bool) $value;
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
}
