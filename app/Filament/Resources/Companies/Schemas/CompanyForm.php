<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Company')
                    ->required()
                    ->maxLength(200),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(300),
                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('city')
                    ->label('Kota')
                    ->required()
                    ->maxLength(100),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->required()
                    ->maxLength(50),
                Textarea::make('bank_account')
                    ->label('Rekening Bank')
                    ->rows(3)
                    ->columnSpanFull(),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('minimum_stock_display')
                            ->label('Minimum Stok Display')
                            ->numeric(),
                        TextInput::make('expiration_month_limit')
                            ->label('Batas Expired (hari)')
                            ->numeric(),
                        TextInput::make('payable_due_month_limit')
                            ->label('Batas Jatuh Tempo (hari)')
                            ->numeric(),
                    ]),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('margin_limit')
                            ->label('Margin Limit')
                            ->numeric(),
                        TextInput::make('ppn')
                            ->label('PPN')
                            ->numeric(),
                        TextInput::make('pph')
                            ->label('PPH')
                            ->numeric(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([                    
                        TextInput::make('footer_text_1')
                            ->label('Footer Text 1')
                            ->maxLength(200),
                        TextInput::make('footer_text_2')
                            ->label('Footer Text 2')
                            ->maxLength(200),
                    ]),    
            ]);
    }
}