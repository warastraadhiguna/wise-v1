<?php

namespace App\Filament\Resources\Stocks\Tables;

use App\Models\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('product_name')
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('product_code')
                    ->label('Kode')
                    ->searchable(['products.code'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('products.code', $direction)),
                TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable(['products.name'])
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('products.name', $direction)),
                TextColumn::make('product_location')
                    ->label('Lokasi')
                    ->placeholder('-')
                    ->searchable(['products.location']),
                TextColumn::make('minimum_stock')
                    ->label('Min Stok')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('qty_on_hand')
                    ->label('Stok Saat Ini')
                    ->numeric(decimalPlaces: 4)
                    ->badge()
                    ->color(fn (Stock $record): string => $record->qty_on_hand <= ($record->minimum_stock ?? 0) ? 'danger' : 'success'),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn (Stock $record): string => $record->qty_on_hand <= ($record->minimum_stock ?? 0) ? 'Di bawah minimum' : 'Aman')
                    ->badge()
                    ->color(fn (Stock $record): string => $record->qty_on_hand <= ($record->minimum_stock ?? 0) ? 'danger' : 'success'),
                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stok di bawah minimum')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stocks.qty_on_hand', '<=', 'products.minimum_stock')),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}

