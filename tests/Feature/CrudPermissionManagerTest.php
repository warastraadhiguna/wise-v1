<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Feature;
use App\Models\FeatureRule;
use App\Models\User;
use App\Support\CrudPermissionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudPermissionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_and_rule_seed_is_created_for_registered_resources(): void
    {
        $manager = app(CrudPermissionManager::class);

        $this->assertSame(11, Feature::count());
        $this->assertSame(11 * count($manager->roles()), FeatureRule::count());
    }

    public function test_role_rule_controls_crud_access_for_a_resource(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $feature = Feature::query()->where('resource', 'product')->firstOrFail();

        FeatureRule::query()
            ->where('feature_id', $feature->id)
            ->where('role', 'user')
            ->update([
                'can_create' => false,
                'can_read' => false,
                'can_update' => false,
                'can_delete' => false,
            ]);

        $manager = app(CrudPermissionManager::class);

        $this->assertFalse($manager->can($user, ProductResource::class, 'read'));
        $this->assertFalse($manager->can($user, ProductResource::class, 'create'));
        $this->assertFalse($manager->can($user, ProductResource::class, 'update'));
        $this->assertFalse($manager->can($user, ProductResource::class, 'delete'));
    }

    public function test_sync_prunes_rules_for_roles_removed_from_config(): void
    {
        config()->set('access.roles', ['superadmin', 'admin']);

        app(CrudPermissionManager::class)->sync();

        $this->assertSame(22, FeatureRule::count());
        $this->assertDatabaseMissing('feature_rules', [
            'role' => 'user',
        ]);
    }
}
