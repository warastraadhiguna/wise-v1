<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Domain\Pos\Actions\RecalculatePurchasePaymentSummary;
use App\Domain\Pos\Actions\PostPurchaseAction;
use App\Models\PaymentMethod;
use App\Models\Purchase;
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

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table    
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('purchase_date')->date()->sortable(),
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
                        fn (Purchase $record): string => 'GT ' . number_format((float) $record->grand_total, 0, ',', '.') .
                            ' | Bayar ' . number_format((float) $record->paid_total, 0, ',', '.') .
                            ' | Sisa ' . number_format((float) $record->balance_due, 0, ',', '.')
                    )
                    ->sortable(),
                TextColumn::make('details_count')->counts('details')->label('Items')->sortable(),
            ])
            ->filters([
                Filter::make('purchase_date')
                    ->label('Tanggal Pembelian')
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
                                fn (Builder $query): Builder => $query->whereDate('purchase_date', '>=', $data['date_start']),
                            )
                            ->when(
                                filled($data['date_end'] ?? null),
                                fn (Builder $query): Builder => $query->whereDate('purchase_date', '<=', $data['date_end']),
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
                        Action::make('print')
                            ->label('Cetak')
                            ->icon('heroicon-o-printer')
                            ->color('gray')
                            ->url(
                                fn (Purchase $record): string => route('purchases.print', ['purchase' => $record->id]),
                                shouldOpenInNewTab: true,
                            ),
                    ])
                    ->modalHeading(fn (Purchase $record): string => "Detail Purchase {$record->number}")
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(function (Purchase $record): View {
                        $record->loadMissing([
                            'supplier:id,name',
                            'paymentMethod:id,name',
                            'details.product:id,code,name',
                        ]);

                        return view('filament.purchases.view-purchase-modal', [
                            'purchase' => $record,
                        ]);
                    }),
                Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->visible(fn (Purchase $record): bool => $record->status === 'posted')
                    ->modalHeading(fn (Purchase $record): string => "Pembayaran Purchase {$record->number}")
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalContent(function (Purchase $record): View {
                        $record->loadMissing([
                            'paymentMethod:id,name',
                            'payments.paymentMethod:id,name',
                            'payments.user:id,name',
                        ]);

                        return view('filament.purchases.payments-history-modal', [
                            'purchase' => $record,
                        ]);
                    })
                    ->modalSubmitAction(fn (Action $action, Purchase $record) => (float) $record->balance_due > 0
                        ? $action->label('Tambah Bayar')
                        : false)
                    ->schema(fn (Purchase $record): array => (float) $record->balance_due > 0
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
                    ->action(function (Purchase $record, array $data, Action $action): void {
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

                        app(RecalculatePurchasePaymentSummary::class)->handle($record->id);

                        Notification::make()
                            ->title('Pembayaran berhasil ditambahkan')
                            ->success()
                            ->send();
                    }),
                Action::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Post Purchase?')
                    ->modalDescription('Setelah diposting, stok akan bertambah dan data tidak bisa diedit.')
                    ->modalSubmitActionLabel('Ya, Post')
                    ->visible(fn (Purchase $record) => $record->status === 'draft')
                    ->action(function (Purchase $record): void {
                        try {
                            app(PostPurchaseAction::class)->handle($record->id, Auth::id());
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
                            ->title('Purchase berhasil diposting')
                            ->success()
                            ->send();
                    }),
                EditAction::make()->visible(fn (Purchase $record) => $record->status === 'draft'),
                DeleteAction::make()
                    ->visible(fn (Purchase $record) => $record->status === 'draft'),
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
            ->defaultSort('purchase_date', 'desc');
    }
}
