<?php

namespace App\Filament\Resources\Features;

use App\Filament\Resources\Features\Pages\EditFeature;
use App\Filament\Resources\Features\Pages\ListFeatures;
use App\Models\Feature;
use App\Support\CrudPermissionManager;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Umum';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        return app(CrudPermissionManager::class)->canManagePermissions(auth()->user());
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fitur')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Fitur')
                            ->disabled(),
                        TextInput::make('navigation_group')
                            ->label('Group Navigasi')
                            ->disabled(),
                    ])
                    ->columns(2),
                Section::make('Rule Akses per Role')
                    ->description('Klik role yang ingin diubah, lalu edit hak aksesnya.')
                    ->schema([
                        Repeater::make('rules')
                            ->relationship('rules')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => static::roleOptions()[$state['role'] ?? ''] ?? null)
                            ->schema([
                                Hidden::make('role'),
                                Grid::make([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 4,
                                ])
                                    ->schema([
                                        Toggle::make('can_create')
                                            ->label('Create'),
                                        Toggle::make('can_read')
                                            ->label('Read'),
                                        Toggle::make('can_update')
                                            ->label('Update'),
                                        Toggle::make('can_delete')
                                            ->label('Delete'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('rules'))
            ->columns([
                TextColumn::make('name')
                    ->label('Fitur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('navigation_group')
                    ->label('Group')
                    ->badge(),
                TextColumn::make('rules_count')
                    ->label('Jumlah Rule')
                    ->alignCenter(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('navigation_group')->orderBy('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatures::route('/'),
            'edit' => EditFeature::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Hak Akses';
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return collect(app(CrudPermissionManager::class)->roles())
            ->mapWithKeys(fn (string $role): array => [$role => (string) str($role)->headline()])
            ->all();
    }
}
