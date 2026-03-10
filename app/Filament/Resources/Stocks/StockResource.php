<?php

namespace App\Filament\Resources\Stocks;

use App\Filament\Resources\Stocks\Pages\ListStocks;
use App\Filament\Resources\Stocks\Tables\StocksTable;
use App\Models\Stock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Data Barang';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArchiveBox;

    public static function table(Table $table): Table
    {
        return StocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStocks::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Stok Realtime';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Stok Realtime';
    }

    public static function getModelLabel(): string
    {
        return 'Stok';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'stocks.*',
                'products.code as product_code',
                'products.name as product_name',
                'products.location as product_location',
                'products.minimum_stock',
            ])
            ->join('products', 'products.id', '=', 'stocks.product_id');
    }
}

