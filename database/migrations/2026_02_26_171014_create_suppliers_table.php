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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('name', 300);
            $table->string('company_name', 300)->nullable();
            $table->text('address');
            $table->string('email', 300)->nullable();
            $table->string('phone', 10);
            $table->string('bank_account', 300)->nullable();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();        
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};