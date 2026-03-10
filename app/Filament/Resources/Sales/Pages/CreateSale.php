<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Domain\Pos\Actions\PostSaleAction;
use App\Domain\Pos\Support\SalePaymentValidator;
use App\Domain\Pos\Support\SaleTotalsCalculator;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Sales\Schemas\SaleForm;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;
    protected bool $shouldPostAfterCreate = false;

    /** @var array<string, mixed> */
    protected array $pendingPostData = [];

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan (F2)')
                ->keyBindings(['f2'])
                ->color('primary'),
            Action::make('createAndPost')
                ->label('Simpan & Post (F6)')
                ->keyBindings(['f6'])
                ->color('success')
                ->modalHeading('Simpan & Post Sale?')
                ->modalDescription('Data akan langsung disimpan dan diposting.')
                ->modalWidth(Width::ThreeExtraLarge)
                ->schema(function (): array {
                    $detailsSubtotal = $this->getPostDetailsSubtotalFromForm();

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
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
                            DatePicker::make('due_date')
                                ->label('Jatuh Tempo')
                                ->default(today())
                                ->visible(fn (Get $get): bool => ! $this->isCashPaymentMethod($get('payment_method_id')))
                                ->required(fn (Get $get): bool => ! $this->isCashPaymentMethod($get('payment_method_id')))
                                ->dehydrateStateUsing(fn ($state, Get $get) => $this->isCashPaymentMethod($get('payment_method_id')) ? null : $state),
                            TextInput::make('discount_percent')
                                ->label('Disc %')
                                ->numeric()
                                ->minValue(0)
                                ->live()
                                ->required()
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
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
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
                            TextInput::make('ppn')
                                ->label('PPN')
                                ->numeric()
                                ->minValue(0)
                                ->live()
                                ->required()
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
                            TextInput::make('pph')
                                ->label('PPH')
                                ->numeric()
                                ->minValue(0)
                                ->live()
                                ->required()
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
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
                                ->afterStateHydrated(fn (Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal))
                                ->afterStateUpdated(fn ($state, Set $set, Get $get) => $this->syncPostModalSummary($set, $get, $detailsSubtotal)),
                            TextInput::make('change_amount_preview')
                                ->label('Uang Kembalian')
                                ->readOnly()
                                ->dehydrated(false)
                                ->visible(fn (Get $get): bool => $this->isCashPaymentMethod($get('payment_method_id')))
                                ->extraInputAttributes([
                                    'class' => 'text-right',
                                    'style' => 'text-align: right;',
                                ]),
                        ]),
                    ];
                })
                ->fillForm(fn (): array => $this->getPostModalInitialDataFromForm())
                ->action(function (array $data): void {
                    $this->shouldPostAfterCreate = true;
                    $this->pendingPostData = $data;
                    $this->create();
                }),
            $this->getCancelFormAction()
                ->label('Cancel (F4)')
                ->keyBindings(['f4'])
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sale_date'] = $data['sale_date'] ?? now()->toDateString();
        $data['number'] = $this->resolveSaleNumber($data);
        $data['discount_percent'] = (float) ($data['discount_percent'] ?? 0);
        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['ppn'] = (float) ($data['ppn'] ?? 0);
        $data['pph'] = (float) ($data['pph'] ?? 0);
        $data['payment_amount'] = (float) ($data['payment_amount'] ?? 0);
        $data['grand_total'] = SalePaymentValidator::calculateGrandTotalFromFormData($data);
        $data['payment_method_id'] = $data['payment_method_id'] ?? null;
        $data['due_date'] = $data['due_date'] ?? null;
        $data['user_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function resolveSaleNumber(array $data): string
    {
        if (filled($data['number'] ?? null)) {
            return (string) $data['number'];
        }

        $generatedNumber = SaleForm::generateNumber($data['sale_date'] ?? now()->toDateString());

        if (blank($generatedNumber)) {
            throw ValidationException::withMessages([
                'number' => 'No nota otomatis tidak tersedia (range 0000-9999 sudah habis).',
            ]);
        }

        return $generatedNumber;
    }

    protected function afterCreate(): void
    {
        // set user_id + remaining_qty untuk detail (draft)
        foreach ($this->record->details()->get() as $d) {
            $d->user_id = Auth::id();
            $d->remaining_qty = $d->qty;
            $d->save();
        }

        if (! $this->shouldPostAfterCreate) {
            return;
        }

        $this->record->payment_method_id = $this->pendingPostData['payment_method_id'] ?? null;
        $this->record->due_date = $this->pendingPostData['due_date'] ?? null;
        $this->record->discount_percent = round((float) ($this->pendingPostData['discount_percent'] ?? 0), 2);
        $this->record->discount_amount = round((float) ($this->pendingPostData['discount_amount'] ?? 0), 2);
        $this->record->ppn = round((float) ($this->pendingPostData['ppn'] ?? 0), 2);
        $this->record->pph = round((float) ($this->pendingPostData['pph'] ?? 0), 2);
        $this->record->payment_amount = round((float) ($this->pendingPostData['payment_amount'] ?? 0), 2);
        $this->record->save();

        app(PostSaleAction::class)->handle((int) $this->record->getKey(), (int) Auth::id());

        Notification::make()
            ->title('Sale berhasil disimpan dan diposting')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        if ($this->shouldPostAfterCreate) {
            return $this->getResource()::getUrl('index');
        }

        return $this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        if ($this->shouldPostAfterCreate) {
            return null;
        }

        return parent::getCreatedNotificationTitle();
    }

    protected function onValidationError(ValidationException $exception): void
    {
        $message = $exception->errors()['payment_amount'][0]
            ?? collect($exception->errors())->flatten()->first();

        if (filled($message)) {
            Notification::make()
                ->title((string) $message)
                ->danger()
                ->send();
        }
    }

    protected function getPostDetailsSubtotalFromForm(): float
    {
        $details = $this->form->getRawState()['details'] ?? [];
        $subtotal = 0.0;

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            $subtotal += SaleTotalsCalculator::lineTotal(
                self::parseLocalizedNumber($detail['qty'] ?? 0),
                self::parseLocalizedNumber($detail['price'] ?? 0),
                self::parseLocalizedNumber($detail['discount_percent'] ?? 0),
                self::parseLocalizedNumber($detail['discount_amount'] ?? 0),
            );
        }

        return round(max(0, $subtotal), 2);
    }

    protected function getPostModalInitialDataFromForm(): array
    {
        $detailsSubtotal = $this->getPostDetailsSubtotalFromForm();
        $paymentMethodId = $this->getDefaultPaymentMethodId();
        $discountPercent = 0.0;
        $discountAmount = 0.0;
        $ppn = 0.0;
        $pph = 0.0;
        $paymentAmount = 0.0;

        $grandTotal = SaleTotalsCalculator::grandTotal(
            $detailsSubtotal,
            $discountPercent,
            $discountAmount,
            $ppn,
            $pph,
        );

        return [
            'payment_method_id' => $paymentMethodId,
            'due_date' => today()->toDateString(),
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'ppn' => $ppn,
            'pph' => $pph,
            'payment_amount' => $paymentAmount,
            'grand_total_preview' => self::formatMoneyInput($grandTotal),
            'change_amount_preview' => self::formatMoneyInput(0),
        ];
    }

    protected function syncPostModalSummary(Set $set, Get $get, float $detailsSubtotal): void
    {
        $grandTotal = SaleTotalsCalculator::grandTotal(
            $detailsSubtotal,
            self::parseLocalizedNumber($get('discount_percent')),
            self::parseLocalizedNumber($get('discount_amount')),
            self::parseLocalizedNumber($get('ppn')),
            self::parseLocalizedNumber($get('pph')),
        );

        $paymentAmount = self::parseLocalizedNumber($get('payment_amount'));
        $kembalian = $this->isCashPaymentMethod($get('payment_method_id'))
            ? max(0, $paymentAmount - $grandTotal)
            : 0;

        $set('grand_total_preview', self::formatMoneyInput($grandTotal));
        $set('change_amount_preview', self::formatMoneyInput($kembalian));
    }

    protected function isCashPaymentMethod(mixed $paymentMethodId): bool
    {
        if (blank($paymentMethodId)) {
            return true;
        }

        $isCash = PaymentMethod::query()
            ->whereKey($paymentMethodId)
            ->value('is_cash');

        return $isCash === null ? true : (bool) $isCash;
    }

    protected function getDefaultPaymentMethodId(): ?int
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
}

