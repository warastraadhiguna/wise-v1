<?php

namespace App\Support;

use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Stocks\StockResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Feature;
use App\Models\FeatureRule;
use App\Models\User;
use Illuminate\Support\Str;

class CrudPermissionManager
{
    /**
     * @return array<int, string>
     */
    public function roles(): array
    {
        return collect(config('access.roles', ['superadmin', 'admin', 'user']))
            ->filter(fn (mixed $role): bool => filled($role))
            ->map(fn (mixed $role): string => trim((string) $role))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function managerRoles(): array
    {
        return collect(config('access.manager_roles', ['superadmin']))
            ->filter(fn (mixed $role): bool => filled($role))
            ->map(fn (mixed $role): string => trim((string) $role))
            ->unique()
            ->values()
            ->all();
    }

    public function canManagePermissions(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, $this->managerRoles(), true);
    }

    /**
     * @return array<int, array{name: string, resource: string, resource_class: string, navigation_group: string|null}>
     */
    public function definitions(): array
    {
        return collect($this->resourceClasses())
            ->map(fn (string $resourceClass): array => [
                'name' => $resourceClass::getNavigationLabel(),
                'resource' => $this->resourceKeyFromClass($resourceClass),
                'resource_class' => $resourceClass,
                'navigation_group' => $resourceClass::getNavigationGroup(),
            ])
            ->values()
            ->all();
    }

    public function sync(): void
    {
        $now = now();
        $roles = $this->roles();

        Feature::query()->upsert(
            collect($this->definitions())
                ->map(fn (array $definition): array => [
                    ...$definition,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all(),
            ['resource'],
            ['name', 'resource_class', 'navigation_group', 'updated_at'],
        );

        $features = Feature::query()
            ->whereIn('resource_class', $this->resourceClasses())
            ->get();

        foreach ($features as $feature) {
            FeatureRule::query()
                ->where('feature_id', $feature->id)
                ->whereNotIn('role', $roles)
                ->delete();

            foreach ($roles as $role) {
                FeatureRule::query()->firstOrCreate(
                    [
                        'feature_id' => $feature->id,
                        'role' => $role,
                    ],
                    [
                        'can_create' => true,
                        'can_read' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ],
                );
            }
        }
    }

    public function can(?User $user, string $resourceClass, string $action): bool
    {
        if (! $user) {
            return false;
        }

        $column = match ($action) {
            'create' => 'can_create',
            'read' => 'can_read',
            'update' => 'can_update',
            'delete' => 'can_delete',
            default => null,
        };

        if (! $column) {
            return false;
        }

        $feature = Feature::query()
            ->with('rules')
            ->where('resource_class', $resourceClass)
            ->orWhere('resource', $this->resourceKeyFromClass($resourceClass))
            ->first();

        if (! $feature) {
            return true;
        }

        $rule = $feature->rules->firstWhere('role', $user->role);

        if (! $rule) {
            return true;
        }

        return (bool) $rule->getAttribute($column);
    }

    protected function resourceKeyFromClass(string $resourceClass): string
    {
        return Str::of(class_basename($resourceClass::getModel()))
            ->snake()
            ->toString();
    }

    /**
     * @return array<int, string>
     */
    protected function resourceClasses(): array
    {
        return [
            BrandResource::class,
            CompanyResource::class,
            CustomerResource::class,
            ProductCategoryResource::class,
            ProductResource::class,
            PurchaseResource::class,
            SaleResource::class,
            StockResource::class,
            SupplierResource::class,
            UnitResource::class,
            UserResource::class,
        ];
    }
}
