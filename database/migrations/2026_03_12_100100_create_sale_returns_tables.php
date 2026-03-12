<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('number', 50)->nullable();
            $table->date('return_date');
            $table->text('reason');
            $table->text('note')->nullable();
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->index(['sale_id', 'return_date'], 'sale_returns_sale_date_idx');
        });

        Schema::create('sale_return_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_return_id')->nullable();
            $table->unsignedBigInteger('sale_detail_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('qty', 12, 4);
            $table->decimal('price', 16, 4);
            $table->decimal('discount_percent', 6, 4)->default(0);
            $table->decimal('discount_amount', 16, 4)->default(0);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('fifo_cost_amount', 16, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->nullOnDelete();
            $table->foreign('sale_detail_id')->references('id')->on('sale_details')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index(['sale_detail_id'], 'srd_sale_detail_idx');
        });

        Schema::create('sale_return_detail_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_return_detail_id')->nullable();
            $table->unsignedBigInteger('sale_detail_fifo_allocation_id')->nullable();
            $table->unsignedBigInteger('purchase_detail_id')->nullable();
            $table->decimal('qty', 12, 4);
            $table->decimal('unit_cost', 16, 4);
            $table->decimal('total_cost', 16, 4);
            $table->timestamps();

            $table->foreign('sale_return_detail_id', 'srda_srd_fk')->references('id')->on('sale_return_details')->nullOnDelete();
            $table->foreign('sale_detail_fifo_allocation_id', 'srda_sdfa_fk')->references('id')->on('sale_detail_fifo_allocations')->nullOnDelete();
            $table->foreign('purchase_detail_id', 'srda_pd_fk')->references('id')->on('purchase_details')->nullOnDelete();
            $table->index(['sale_detail_fifo_allocation_id'], 'srda_sdfa_idx');
            $table->index(['purchase_detail_id'], 'srda_pd_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_detail_allocations');
        Schema::dropIfExists('sale_return_details');
        Schema::dropIfExists('sale_returns');
    }
};
