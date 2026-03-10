<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Domain\Pos\Support\PurchasePaymentValidator;
use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan (F2)')
                ->keyBindings(['f2'])
                ->color('primary'),
            $this->getCancelFormAction()
                ->label('Cancel (F4)')
                ->keyBindings(['f4'])
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = PurchasePaymentValidator::normalizeAndValidateDraft($data);
        $data['user_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        // set user_id + remaining_qty untuk detail (draft)
        foreach ($this->record->details()->get() as $d) {
            $d->user_id = Auth::id();
            $d->remaining_qty = $d->qty;
            $d->save();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]);
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
