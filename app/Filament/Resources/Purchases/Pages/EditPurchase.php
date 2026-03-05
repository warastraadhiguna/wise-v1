<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Domain\Pos\Actions\PostPurchaseAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;
    protected Width | string | null $maxContentWidth = Width::Full;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Purchase?')
                ->modalDescription('Setelah diposting, stok akan bertambah dan data tidak bisa diedit.')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    app(PostPurchaseAction::class)->handle($this->record->id, Auth::id());

                    Notification::make()
                        ->title('Purchase berhasil diposting')
                        ->success()
                        ->send();

                    // balik ke list / atau stay di edit
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
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