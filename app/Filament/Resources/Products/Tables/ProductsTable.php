<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('productCategory.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Satuan')
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('prices_summary')
                //     ->label('Harga')
                //     ->state(function (Product $record): array {
                //         $prices = $record->prices;

                //         if (! $prices instanceof Collection) {
                //             return [];
                //         }

                //         return $prices
                //             ->sortBy(fn ($price) => $price->priceType?->index ?? PHP_INT_MAX)
                //             ->map(function ($price): string {
                //                 $label = $price->priceType?->name ?? ('Tipe ' . $price->price_type_id);
                //                 $amount = number_format((float) $price->price, 2, ',', '.');

                //                 return "{$label}: {$amount}";
                //             })
                //             ->values()
                //             ->all();
                //     })
                //     ->listWithLineBreaks()
                //     ->wrap(),
                TextColumn::make('minimum_stock')
                    ->label('Min Stok')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                IconColumn::make('input_status')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('editPrices')
                    ->label('Ubah Harga')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Product $record): bool => ! $record->trashed())
                    ->modalHeading(fn (Product $record): string => "Ubah Harga: {$record->name}")
                    ->modalSubmitActionLabel('Simpan Harga')
                    ->fillForm(fn (Product $record): array => [
                        'price_map' => ProductForm::buildPriceMapFromProduct($record),
                    ])
                    ->form(ProductForm::buildPriceTypeFields())
                    ->action(function (Product $record, array $data): void {
                        ProductForm::syncPrices($record, $data['price_map'] ?? []);

                        Notification::make()
                            ->title('Harga produk berhasil diperbarui.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}