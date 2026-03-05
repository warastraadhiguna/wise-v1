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
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

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
                    ->visible(fn (Purchase $record): bool => $record->status === 'posted' && (float) $record->balance_due > 0)
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
                        TextInput::make('amount')
                            ->label('Jumlah Bayar')
                            ->type('text')
                            ->mask(RawJs::make(<<<'JS'
                                $money($input, ',', '.', 0)
                            JS))
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
                    ])
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
                        app(PostPurchaseAction::class)->handle($record->id, Auth::id());

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
