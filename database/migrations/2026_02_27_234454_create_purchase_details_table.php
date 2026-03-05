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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger(column: 'user_id')->nullable();   
            $table->unsignedBigInteger('purchase_id')->nullable();    
            $table->unsignedBigInteger('product_id')->nullable();    
            $table->decimal('qty', 12 , 4);   
            $table->decimal('remaining_qty', 16 , 4)->default(0);   
            $table->decimal('price', 16 , 4);               
            $table->decimal('discount_percent', 6 , 4);    
            $table->decimal('discount_amount', 16 , 4);  

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();  
            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();  
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();              
            $table->timestamps();
            $table->softDeletes();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};