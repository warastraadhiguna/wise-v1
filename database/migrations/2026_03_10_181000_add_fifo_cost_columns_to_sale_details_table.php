<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('fifo_cost_amount', 16, 4)->default(0)->after('discount_amount');
            $table->decimal('margin_amount', 16, 4)->default(0)->after('fifo_cost_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['fifo_cost_amount', 'margin_amount']);
        });
    }
};
