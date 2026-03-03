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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger(column: 'user_id')->nullable();   
            $table->unsignedBigInteger('supplier_id')->nullable();            
            $table->unsignedTinyInteger('payment_method_id')->nullable();                            
            $table->string('number', 50);          
            $table->date('purchase_date');  
            $table->date('due_date')->nullable();                         
            $table->decimal('discount_percent', 6 , 2);    
            $table->decimal('discount_amount', 12 , 2);   
            $table->decimal('ppn', 6 , 2);  
            $table->decimal('pph', 6 , 2);   
            $table->decimal('payment_amount', 12 , 2);                          
            $table->string('reference_number', 50);   
            $table->text('note');     
            $table->string('status', 20)->default('draft'); // draft|posted|void
            $table->timestamp('posted_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();   
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();   
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();  
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};