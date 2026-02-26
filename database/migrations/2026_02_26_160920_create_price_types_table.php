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
        Schema::create('price_types', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->tinyInteger('index');
            $table->string('name', 50);
        });

        DB::table('price_types')->insert([
            ['id' => 1, 'index' => 1 , 'name' => 'Harga Normal'],
            ['id' => 2, 'index' => 2, 'name' => 'Harga Grosir 1'],
            ['id' => 3, 'index' => 3, 'name' => 'Harga Grosir 2'],
        ]);        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_types');
    }
};