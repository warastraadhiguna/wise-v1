<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
    protected function getFormActions(): array
    {
        return [
          $this->getCreateFormAction()  // Tombol Create default
        ->label('Create')
        ->color('primary'),
          $this->getCancelFormAction()  // Tombol Cancel default
        ->label('Batal')
            ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman index setelah create
        return $this->getResource()::getUrl('index');
    }    
}