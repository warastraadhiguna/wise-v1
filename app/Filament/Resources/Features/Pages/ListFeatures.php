<?php

namespace App\Filament\Resources\Features\Pages;

use App\Filament\Resources\Features\FeatureResource;
use App\Support\CrudPermissionManager;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListFeatures extends ListRecords
{
    protected static string $resource = FeatureResource::class;

    public function mount(): void
    {
        app(CrudPermissionManager::class)->sync();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync Fitur')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    app(CrudPermissionManager::class)->sync();
                }),
        ];
    }
}
