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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('user_id')->nullable();                
            $table->unsignedSmallInteger('brand_id')->nullable();    
            $table->unsignedSmallInteger('product_category_id')->nullable();    
            $table->unsignedSmallInteger('unit_id')->nullable();          
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();     
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();     
            $table->foreign('product_category_id')->references('id')->on('product_categories')->nullOnDelete();     
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();     

            $table->string('code', 20)->unique(); 
            $table->string('name', 200); 
            $table->string('type', 500)->nullable(); 
            $table->text('location')->nullable();
            $table->text('description')->nullable(); 
            $table->text('unit_notes')->nullable(); 
            $table->text('price_notes')->nullable(); 
            $table->float('minimum_stock')->default(0);          
            $table->boolean('input_status')->default(true);            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};