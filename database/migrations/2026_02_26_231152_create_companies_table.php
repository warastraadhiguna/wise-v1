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
        Schema::create('companies', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->string('city', 100);
            $table->string('phone', 50);
            $table->string('email', 300)->nullable();
            $table->text('bank_account')->nullable();
            $table->float('minimum_stock_display')->nullable();
            $table->integer('expiration_month_limit')->nullable();
            $table->string('footer_text_1', 200)->nullable();
            $table->string('footer_text_2', 200)->nullable();         
            $table->integer('payable_due_month_limit')->nullable();               
            $table->float('margin_limit')->nullable();
            $table->float('ppn')->nullable(); 
            $table->float('pph')->nullable();         
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};