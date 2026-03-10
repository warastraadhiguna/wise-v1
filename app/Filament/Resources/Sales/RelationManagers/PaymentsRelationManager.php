<?php

namespace App\Filament\Resources\Sales\RelationManagers;

use App\Domain\Pos\Actions\RecalculateSalePaymentSummary;
use App\Models\PaymentMethod;
use App\Models\SalePayment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pembayaran')
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Cara Bayar')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('No Ref')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(60)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('paid_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Bayar')
                    ->visible(fn (): bool => $this->canCreatePayment())
                    ->schema($this->getPaymentFormSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['amount'] = round(self::parseLocalizedNumber($data['amount'] ?? 0), 2);
                        $data['user_id'] = Auth::id();

                        return $data;
                    })
                    ->before(fn (CreateAction $action) => $this->validatePaymentAmount($action))
                    ->after(fn () => $this->syncPaymentSummary()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->visible(fn (): bool => $this->canManagePayments())
                    ->schema($this->getPaymentFormSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['amount'] = round(self::parseLocalizedNumber($data['amount'] ?? 0), 2);
                        $data['user_id'] = Auth::id();

                        return $data;
                    })
                    ->before(fn (EditAction $action) => $this->validatePaymentAmount($action))
                    ->after(fn () => $this->syncPaymentSummary()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManagePayments())
                    ->after(fn () => $this->syncPaymentSummary()),
            ]);
    }

    protected function getPaymentFormSchema(): array
    {
        return [
            Select::make('payment_method_id')
                ->label('Cara Bayar')
                ->relationship(
                    'paymentMethod',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => $query->orderBy('index'),
                )
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('amount')
                ->label('Jumlah Bayar')
                ->type('text')
                ->mask(RawJs::make(<<<'JS'
                    $money($input, ',', '.', 0)
                JS))
                ->formatStateUsing(fn ($state): string => self::formatMoneyInput($state))
                ->dehydrateStateUsing(fn ($state): float => round(self::parseLocalizedNumber($state), 2))
                ->required()
                ->extraInputAttributes([
                    'class' => 'text-right',
                    'style' => 'text-align: right;',
                    'inputmode' => 'numeric',
                ]),
            DatePicker::make('paid_at')
                ->label('Tanggal Bayar')
                ->default(now())
                ->required(),
            TextInput::make('reference_number')
                ->label('No Ref')
                ->maxLength(100),
            Textarea::make('note')
                ->label('Catatan')
                ->rows(2),
        ];
    }

    protected function validatePaymentAmount(Action $action): void
    {
        $data = $action->getData();
        $amount = self::parseLocalizedNumber($data['amount'] ?? 0);

        if ($amount <= 0) {
            Notification::make()
                ->title('Jumlah bayar harus lebih dari 0')
                ->warning()
                ->send();

            $action->halt();

            return;
        }

        $sale = $this->getOwnerRecord()->refresh();
        $balanceDue = max(0, (float) ($sale->balance_due ?? 0));

        $editingRecord = $action->getRecord();
        $editingAmount = $editingRecord instanceof SalePayment
            ? (float) $editingRecord->amount
            : 0.0;

        $maxAllowed = $balanceDue + $editingAmount;

        if ($maxAllowed <= 0) {
            Notification::make()
                ->title('Sale ini sudah lunas')
                ->warning()
                ->send();

            $action->halt();

            return;
        }

        if ($amount > $maxAllowed) {
            Notification::make()
                ->title('Jumlah bayar melebihi sisa tagihan')
                ->body('Maksimal pembayaran: ' . number_format($maxAllowed, 0, ',', '.'))
                ->warning()
                ->send();

            $action->halt();
        }
    }

    protected function canManagePayments(): bool
    {
        return (string) $this->getOwnerRecord()->status === 'posted';
    }

    protected function canCreatePayment(): bool
    {
        if (! $this->canManagePayments()) {
            return false;
        }

        return (float) $this->getOwnerRecord()->refresh()->balance_due > 0;
    }

    protected function syncPaymentSummary(): void
    {
        app(RecalculateSalePaymentSummary::class)->handle((int) $this->getOwnerRecord()->getKey());

        $this->getOwnerRecord()->refresh();
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

