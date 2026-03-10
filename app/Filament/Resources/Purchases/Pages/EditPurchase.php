<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Domain\Pos\Support\PurchasePaymentValidator;
use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Domain\Pos\Actions\PostPurchaseAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Validation\ValidationException;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;
    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan (F2)')
                ->keyBindings(['f2'])
                ->color('primary'),
            $this->getCancelFormAction()
                ->label('Cancel (F4)')
                ->keyBindings(['f4'])
                ->color('gray'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post (F6)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->keyBindings(['f6'])
                ->requiresConfirmation()
                ->modalHeading('Post Purchase?')
                ->modalDescription('Setelah diposting, stok akan bertambah dan data tidak bisa diedit.')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    try {
                        app(PostPurchaseAction::class)->handle($this->record->id, Auth::id());
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

                    // balik ke list / atau stay di edit
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->label('Hapus (F8)')
                ->keyBindings(['f8'])
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    protected function beforeFill(): void
    {
        // kalau posted, jangan bisa edit
        if ((string) $this->getRecord()->getAttribute('status') !== 'draft') {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['grand_total'] = PurchasePaymentValidator::calculateGrandTotalFromFormData($data);
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterSave(): void
    {
        // karena masih draft, remaining_qty selalu disamakan dengan qty
        foreach ($this->record->details()->get() as $d) {
            $d->user_id = Auth::id();
            $d->remaining_qty = $d->qty;
            $d->save();
        }
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
}
