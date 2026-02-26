<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(300),
                TextInput::make('company_name')
                    ->label('Nama Perusahaan')
                    ->maxLength(300),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(300),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel()
                    ->required()
                    ->maxLength(10),
                TextInput::make('bank_account')
                    ->label('Rekening Bank')
                    ->maxLength(300),
                Select::make('price_type_id')
                    ->label('Tipe Harga')
                    ->relationship('priceType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),                    
                TextInput::make('point')
                    ->label('Poin')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}