<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_detail_fifo_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_detail_id')->nullable();
            $table->unsignedBigInteger('purchase_detail_id')->nullable();
            $table->decimal('qty', 12, 4);
            $table->decimal('unit_cost', 16, 4);
            $table->decimal('total_cost', 16, 4);
            $table->timestamps();

            $table->foreign('sale_detail_id')->references('id')->on('sale_details')->nullOnDelete();
            $table->foreign('purchase_detail_id')->references('id')->on('purchase_details')->nullOnDelete();
            $table->index(['sale_detail_id']);
            $table->index(['purchase_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_detail_fifo_allocations');
    }
};
