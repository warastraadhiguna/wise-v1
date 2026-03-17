<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\CrudPermissionManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('role')
                    ->options(
                        collect(app(CrudPermissionManager::class)->roles())
                            ->mapWithKeys(fn (string $role): array => [$role => (string) str($role)->headline()])
                            ->all()
                    )
                    ->required()
                    ->default((string) config('access.default_role', 'admin')),
                TextInput::make('password')
                    ->label('Password (kosongi jika tidak mengubah)')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255),
            ]);
    }
}
