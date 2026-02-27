<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mutateGeneratedCode($data);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create')
                ->color('primary'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateGeneratedCode(array $data): array
    {
        if (filled($data['code'] ?? null)) {
            return $data;
        }

        if (
            blank($data['brand_id'] ?? null) ||
            blank($data['product_category_id'] ?? null) ||
            blank($data['unit_id'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'code' => 'Isi brand, kategori produk, dan satuan dulu, lalu generate code.',
            ]);
        }

        $generatedCode = ProductForm::generateCode(
            (int) $data['brand_id'],
            (int) $data['product_category_id'],
            (int) $data['unit_id'],
        );

        if (blank($generatedCode)) {
            throw ValidationException::withMessages([
                'code' => 'Kode otomatis tidak tersedia (range 00001-99999 sudah habis).',
            ]);
        }

        $data['code'] = $generatedCode;

        return $data;
    }
}
