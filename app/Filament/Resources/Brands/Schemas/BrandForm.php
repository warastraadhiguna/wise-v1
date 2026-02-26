<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id()),
                TextInput::make('name')
                    ->label('Nama Brand')
                    ->required()
                    ->maxLength(100),
                TextInput::make('discount')
                    ->label('Diskon')
                    ->numeric()
                    ->required(),
                Textarea::make('note')
                    ->label('Keterangan')
                    ->required()
                    ->maxLength(200)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
