<?php

use App\Support\CrudPermissionManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('resource')->unique();
            $table->string('resource_class')->nullable()->unique();
            $table->string('navigation_group')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->string('role', 20);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['feature_id', 'role']);
        });

        app(CrudPermissionManager::class)->sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_rules');
        Schema::dropIfExists('features');
    }
};
