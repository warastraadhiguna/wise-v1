<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::canCrudAction('read');
    }

    public static function canView(Model $record): bool
    {
        return static::canCrudAction('read');
    }

    public static function canCreate(): bool
    {
        return static::canCrudAction('create') && static::passesCreateBusinessRules();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCrudAction('update') && static::passesEditBusinessRules($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCrudAction('delete') && static::passesDeleteBusinessRules($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::canCrudAction('delete');
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canDelete($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canDeleteAny();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canCrudAction('update');
    }

    public static function canRestoreAny(): bool
    {
        return static::canCrudAction('update');
    }

    public static function canReplicate(Model $record): bool
    {
        return static::canCrudAction('create');
    }

    public static function canReorder(): bool
    {
        return static::canCrudAction('update');
    }

    protected static function canCrudAction(string $action): bool
    {
        return auth()->user()?->canCrud(static::class, $action) ?? false;
    }

    protected static function passesCreateBusinessRules(): bool
    {
        return true;
    }

    protected static function passesEditBusinessRules(Model $record): bool
    {
        return true;
    }

    protected static function passesDeleteBusinessRules(Model $record): bool
    {
        return true;
    }
}
