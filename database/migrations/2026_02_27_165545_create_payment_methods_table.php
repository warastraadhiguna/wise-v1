<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->tinyInteger('index');
            $table->string('name', 50);
        });

        DB::table('payment_methods')->insert([
            ['id' => 1, 'index' => 1 , 'name' => 'Tunai'],
            ['id' => 2, 'index' => 2, 'name' => 'Hutang'],
            ['id' => 3, 'index' => 3, 'name' => 'Debit'],
            ['id' => 4, 'index' => 4, 'name' => 'Kredit'],            
            ['id' => 5, 'index' => 5, 'name' => 'QRIS'],               
        ]);   
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};