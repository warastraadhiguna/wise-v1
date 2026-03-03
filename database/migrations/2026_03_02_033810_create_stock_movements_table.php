<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();            
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_change', 16, 4); // + masuk, - keluar
            $table->string('ref_type', 30);       // PURCHASE, SALE, ADJUST, dll
            $table->unsignedBigInteger('ref_id'); // id transaksi sumber
            $table->decimal('balance_after', 16, 4)->nullable(); // opsional, enak utk audit
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['ref_type', 'ref_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};