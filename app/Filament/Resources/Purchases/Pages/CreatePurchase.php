<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
}
