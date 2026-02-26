<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->redirect(
            CompanyResource::getUrl('edit', ['record' => CompanyResource::getSingletonRecord()->getKey()]),
            navigate: true,
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
