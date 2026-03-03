<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function beforeFill(): void
    {
        // kalau posted, jangan bisa edit
        if ((string) $this->getRecord()->getAttribute('status') !== 'draft') {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
}
