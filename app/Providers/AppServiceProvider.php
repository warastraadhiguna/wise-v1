<?php

namespace App\Providers;

use App\Support\CrudPermissionManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(200);

        if (
            Schema::hasTable('features') &&
            Schema::hasTable('feature_rules')
        ) {
            app(CrudPermissionManager::class)->sync();
        }
    }
}
