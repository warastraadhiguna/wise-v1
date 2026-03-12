<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('number', 50)->nullable();
            $table->date('return_date');
            $table->text('reason');
            $table->text('note')->nullable();
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->index(['purchase_id', 'return_date']);
        });

        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id')->nullable();
            $table->unsignedBigInteger('purchase_detail_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('qty', 12, 4);
            $table->decimal('price', 16, 4);
            $table->decimal('discount_percent', 6, 4)->default(0);
            $table->decimal('discount_amount', 16, 4)->default(0);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->nullOnDelete();
            $table->foreign('purchase_detail_id')->references('id')->on('purchase_details')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->index(['purchase_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_details');
        Schema::dropIfExists('purchase_returns');
    }
};
