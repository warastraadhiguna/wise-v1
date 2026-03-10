<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Domain\Pos\Actions\RecalculateSalePaymentSummary;
use App\Domain\Pos\Actions\PostSaleAction;
use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Models\PaymentMethod;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table    
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('sale_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->toggleable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Cara Bayar')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'void' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                        'paid' => 'success',
                        default => 'secondary',
                    })
                    ->description(
                        fn (Sale $record): string => collect([
                            'GT ' . number_format((float) $record->grand_total, 0, ',', '.'),
                            'Bayar ' . number_format((float) $record->paid_total, 0, ',', '.'),
                            'Sisa ' . number_format((float) $record->balance_due, 0, ',', '.'),
                            (float) $record->paid_total > (float) $record->grand_total
                                ? 'Kembali ' . number_format((float) $record->paid_total - (float) $record->grand_total, 0, ',', '.')
                                : null,
                        ])->filter()->implode(' | ')
                    )
                    ->sortable(),
                TextColumn::make('details_count')->counts('details')->label('Items')->sortable(),
            ])
            ->filters([
                Filter::make('sale_date')
                    ->label('Tanggal Penjualan')
                    ->schema([
                        DatePicker::make('date_start')
                            ->label('Dari Tanggal')
                            ->default(today()),
                        DatePicker::make('date_end')
                            ->label('Sampai Tanggal')
                            ->default(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['date_start'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('sale_date', '>=', $data['date_start']),
                            )
                            ->when(
                                filled($data['date_end'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('sale_date', '<=', $data['date_end']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['date_start'] ?? null)) {
                            $indicators[] = 'Dari: ' . date('d/m/Y', strtotime((string) $data['date_start']));
                        }

                        if (filled($data['date_end'] ?? null)) {
                            $indicators[] = 'Sampai: ' . date('d/m/Y', strtotime((string) $data['date_end']));
                        }

                        return $indicators;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->extraModalFooterActions([
                        Action::make('viewPrintNota')
                            ->label('Cetak Nota')
                            ->icon('heroicon-o-printer')
                            ->color('gray')
                            ->url(
                                fn (Sale $record): string => route('sales.print', ['sale' => $record->id, 'type' => 'nota']),
                                shouldOpenInNewTab: true,
                            )
                            ->visible(fn (Sale $record): bool => $record->status === 'posted'),
                        Action::make('viewPrintStruk')
                            ->label('Cetak Struk')
                            ->icon('heroicon-o-ticket')
                            ->color('gray')
                            ->url(
                                fn (Sale $record): string => route('sales.print', ['sale' => $record->id, 'type' => 'struk']),
                                shouldOpenInNewTab: true,
                            )
                            ->visible(fn (Sale $record): bool => $record->status === 'posted'),
                    ])
                    ->modalHeading(fn (Sale $record): string => "Detail Sale {$record->number}")
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(function (Sale $record): View {
                        $record->loadMissing([
                            'customer:id,name',
                            'paymentMethod:id,name',
                            'details.product:id,code,name',
                        ]);

                        return view('filament.sales.view-sale-modal', [
                            'sale' => $record,
                        ]);
                    }),
                Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn (Sale $record): bool => $record->status === 'posted')
                    ->modalHeading(fn (Sale $record): string => "Pembayaran Sale {$record->number}")
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalContent(function (Sale $record): View {
                        $record->loadMissing([
                            'paymentMethod:id,name',
                            'payments.paymentMethod:id,name',
                            'payments.user:id,name',
                        ]);

                        return view('filament.sales.payments-history-modal', [
                            'sale' => $record,
                        ]);
                    })
                    ->modalSubmitAction(fn (Action $action, Sale $record) => (float) $record->balance_due > 0
                        ? $action->label('Tambah Bayar')
                        : false)
                    ->schema(fn (Sale $record): array => (float) $record->balance_due > 0
                        ? [
                            Grid::make(2)
                                ->schema([
                                    Select::make('payment_method_id')
                                        ->label('Cara Bayar')
                                        ->options(fn (): array => PaymentMethod::query()
                                            ->orderBy('index')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->required()
                                        ->searchable()
                                        ->preload(),
                                    DatePicker::make('paid_at')
                                        ->label('Tanggal Bayar')
                                        ->default(now())
                                        ->required(),
                                    TextInput::make('amount')
                                        ->label('Jumlah Bayar')
                                        ->type('text')
                                        ->mask(RawJs::make(<<<'JS'
                                            $money($input, ',', '.', 0)
                                        JS))
                                        ->required()
                                        ->extraInputAttributes([
                                            'class' => 'text-left',
                                            'style' => 'text-align: left;',
                                            'inputmode' => 'numeric',
                                        ]),
                                    TextInput::make('reference_number')
                                        ->label('No Ref')
                                        ->maxLength(100),
                                    Textarea::make('note')
                                        ->label('Catatan')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),
                        ]
                        : [])
                    ->action(function (Sale $record, array $data, Action $action): void {
                        $amount = (float) (preg_replace('/\D/', '', (string) ($data['amount'] ?? 0)) ?: 0);

                        if ($amount <= 0) {
                            Notification::make()
                                ->title('Jumlah bayar harus lebih dari 0')
                                ->warning()
                                ->send();

                            $action->halt();

                            return;
                        }

                        $record->refresh();
                        $balanceDue = max(0, (float) $record->balance_due);

                        if ($amount > $balanceDue) {
                            Notification::make()
                                ->title('Jumlah bayar melebihi sisa tagihan')
                                ->body('Maksimal pembayaran: ' . number_format($balanceDue, 0, ',', '.'))
                                ->warning()
                                ->send();

                            $action->halt();

                            return;
                        }

                        $record->payments()->create([
                            'payment_method_id' => $data['payment_method_id'],
                            'user_id' => Auth::id(),
                            'amount' => round($amount, 2),
                            'paid_at' => $data['paid_at'],
                            'reference_number' => $data['reference_number'] ?? null,
                            'note' => $data['note'] ?? null,
                        ]);

                        app(RecalculateSalePaymentSummary::class)->handle($record->id);

                        Notification::make()
                            ->title('Pembayaran berhasil ditambahkan')
                            ->success()
                            ->send();
                    }),
                Action::make('printNota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (Sale $record): bool => $record->status === 'posted')
                    ->url(
                        fn (Sale $record): string => route('sales.print', ['sale' => $record->id, 'type' => 'nota']),
                        shouldOpenInNewTab: true,
                    ),
                Action::make('printStruk')
                    ->label('Cetak Struk')
                    ->icon('heroicon-o-ticket')
                    ->color('gray')
                    ->visible(fn (Sale $record): bool => $record->status === 'posted')
                    ->url(
                        fn (Sale $record): string => route('sales.print', ['sale' => $record->id, 'type' => 'struk']),
                        shouldOpenInNewTab: true,
                    ),
                Action::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Post Sale?')
                    ->modalDescription('Setelah diposting, stok akan berkurang dan data tidak bisa diedit.')
                    ->modalSubmitActionLabel('Ya, Post')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->schema(function (Sale $record): array {
                        $detailsSubtotal = self::getPostDetailsSubtotal($record);

                        return [
                            Grid::make(2)->schema([
                                Select::make('payment_method_id')
                                    ->label('Cara Bayar')
                                    ->options(fn (): array => PaymentMethod::query()
                                        ->orderBy('index')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                DatePicker::make('due_date')
                                    ->label('Jatuh Tempo')
                                    ->default(today())
                                    ->visible(fn (Get $get): bool => ! self::isCashPaymentMethod($get('payment_method_id')))
                                    ->required(fn (Get $get): bool => ! self::isCashPaymentMethod($get('payment_method_id')))
                                    ->dehydrateStateUsing(fn ($state, Get $get) => self::isCashPaymentMethod($get('payment_method_id')) ? null : $state),
                                TextInput::make('discount_percent')
                                    ->label('Disc %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live()
                                    ->required()
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                TextInput::make('discount_amount')
                                    ->label('Disc Rp')
                                    ->type('text')
                                    ->mask(RawJs::make(<<<'JS'
                                        $money($input, ',', '.', 0)
                                    JS))
                                    ->live()
                                    ->formatStateUsing(fn ($state): string => self::formatMoneyInput($state))
                                    ->dehydrateStateUsing(fn ($state): float => round(self::parseLocalizedNumber($state), 2))
                                    ->required()
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                TextInput::make('ppn')
                                    ->label('PPN')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live()
                                    ->required()
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                TextInput::make('pph')
                                    ->label('PPH')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live()
                                    ->required()
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                TextInput::make('grand_total_preview')
                                    ->label('Grand Total')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->extraInputAttributes([
                                        'class' => 'text-right',
                                        'style' => 'text-align: right;',
                                    ]),
                                TextInput::make('payment_amount')
                                    ->label('Jumlah Bayar')
                                    ->type('text')
                                    ->mask(RawJs::make(<<<'JS'
                                        $money($input, ',', '.', 0)
                                    JS))
                                    ->live()
                                    ->formatStateUsing(fn ($state): string => self::formatMoneyInput($state))
                                    ->dehydrateStateUsing(fn ($state): float => round(self::parseLocalizedNumber($state), 2))
                                    ->required()
                                    ->extraInputAttributes([
                                        'x-init' => '$nextTick(() => { $el.focus(); $el.select(); })',
                                        'x-on:focus' => '$el.select()',
                                    ])
                                    ->afterStateHydrated(fn (Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal))
                                    ->afterStateUpdated(fn ($state, Set $set, Get $get) => self::syncPostModalSummary($set, $get, $detailsSubtotal)),
                                TextInput::make('change_amount_preview')
                                    ->label('Uang Kembalian')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->visible(fn (Get $get): bool => self::isCashPaymentMethod($get('payment_method_id')))
                                    ->extraInputAttributes([
                                        'class' => 'text-right',
                                        'style' => 'text-align: right;',
                                    ]),
                            ]),
                        ];
                    })
                    ->fillForm(fn (Sale $record): array => [
                        ...self::getPostModalInitialData($record),
                    ])
                    ->visible(fn (Sale $record) => $record->status === 'draft')
                    ->action(function (Sale $record, array $data): void {
                        $record->payment_method_id = $data['payment_method_id'] ?? null;
                        $record->due_date = $data['due_date'] ?? null;
                        $record->discount_percent = round((float) ($data['discount_percent'] ?? 0), 2);
                        $record->discount_amount = round((float) ($data['discount_amount'] ?? 0), 2);
                        $record->ppn = round((float) ($data['ppn'] ?? 0), 2);
                        $record->pph = round((float) ($data['pph'] ?? 0), 2);
                        $record->payment_amount = round((float) ($data['payment_amount'] ?? 0), 2);
                        $record->save();

                        try {
                            app(PostSaleAction::class)->handle($record->id, Auth::id());
                        } catch (ValidationException $exception) {
                            $message = $exception->errors()['payment_amount'][0]
                                ?? collect($exception->errors())->flatten()->first()
                                ?? 'Data belum valid.';

                            Notification::make()
                                ->title((string) $message)
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Sale berhasil diposting')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->visible(fn (Sale $record) => $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn (Sale $record) => $record->status === 'draft'),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    protected static function formatMoneyInput(mixed $state): string
    {
        return number_format(self::parseLocalizedNumber($state), 0, ',', '.');
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
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif (str_contains($normalized, '.')) {
            if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $normalized)) {
                $normalized = str_replace('.', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $normalized)) {
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace(',', '.', $normalized);
            }
        }

        $normalized = preg_replace('/[^\d.-]/', '', $normalized) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === '-.') {
            return 0;
        }

        return (float) $normalized;
    }

    protected static function getPostDetailsSubtotal(Sale $record): float
    {
        $details = $record->details()->get();

        return SaleTotalsCalculator::detailsSubtotal($details);
    }

    protected static function getPostModalInitialData(Sale $record): array
    {
        $detailsSubtotal = self::getPostDetailsSubtotal($record);

        $discountPercent = round((float) $record->discount_percent, 2);
        $discountAmount = round((float) $record->discount_amount, 2);
        $ppn = round((float) $record->ppn, 2);
        $pph = round((float) $record->pph, 2);
        $paymentAmount = round((float) $record->payment_amount, 2);

        $grandTotal = SaleTotalsCalculator::grandTotal(
            $detailsSubtotal,
            $discountPercent,
            $discountAmount,
            $ppn,
            $pph,
        );

        $kembalian = max(0, $paymentAmount - $grandTotal);

        return [
            'payment_method_id' => $record->payment_method_id ?: self::getDefaultPaymentMethodId(),
            'due_date' => $record->due_date?->toDateString(),
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'ppn' => $ppn,
            'pph' => $pph,
            'payment_amount' => $paymentAmount,
            'grand_total_preview' => self::formatMoneyInput($grandTotal),
            'change_amount_preview' => self::formatMoneyInput($kembalian),
        ];
    }

    protected static function syncPostModalSummary(Set $set, Get $get, float $detailsSubtotal): void
    {
        $grandTotal = SaleTotalsCalculator::grandTotal(
            $detailsSubtotal,
            self::parseLocalizedNumber($get('discount_percent')),
            self::parseLocalizedNumber($get('discount_amount')),
            self::parseLocalizedNumber($get('ppn')),
            self::parseLocalizedNumber($get('pph')),
        );

        $paymentAmount = self::parseLocalizedNumber($get('payment_amount'));
        $kembalian = self::isCashPaymentMethod($get('payment_method_id'))
            ? max(0, $paymentAmount - $grandTotal)
            : 0;

        $set('grand_total_preview', self::formatMoneyInput($grandTotal));
        $set('change_amount_preview', self::formatMoneyInput($kembalian));
    }

    protected static function isCashPaymentMethod(mixed $paymentMethodId): bool
    {
        if (blank($paymentMethodId)) {
            return true;
        }

        $isCash = PaymentMethod::query()
            ->whereKey($paymentMethodId)
            ->value('is_cash');

        return $isCash === null ? true : (bool) $isCash;
    }

    protected static function getDefaultPaymentMethodId(): ?int
    {
        $paymentMethodId = PaymentMethod::query()
            ->where('index', 1)
            ->orderBy('id')
            ->value('id');

        if (! filled($paymentMethodId)) {
            $paymentMethodId = PaymentMethod::query()
                ->orderBy('index')
                ->orderBy('id')
                ->value('id');
        }

        return filled($paymentMethodId) ? (int) $paymentMethodId : null;
    }
}

