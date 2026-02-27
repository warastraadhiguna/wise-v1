<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('product_category_id')
                            ->label('Kategori Produk')
                            ->relationship('productCategory', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(200),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([                    
                    Textarea::make('location')
                        ->label('Lokasi')
                        ->rows(2),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('unit_notes')
                            ->label('Catatan Satuan')
                            ->rows(2),
                        Textarea::make('price_notes')
                            ->label('Catatan Harga')
                            ->rows(2),
                    ]),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                    TextInput::make('type')
                        ->label('Tipe')
                        ->maxLength(500),                        
                        TextInput::make('minimum_stock')
                            ->label('Minimum Stok')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('input_status')
                            ->label('Input Status')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}