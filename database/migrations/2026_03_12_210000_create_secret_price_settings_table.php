<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_price_settings', function (Blueprint $table) {
            $table->id();
            $table->string('digit_0', 20)->default('K');
            $table->string('digit_1', 20)->default('D');
            $table->string('digit_2', 20)->default('A');
            $table->string('digit_3', 20)->default('N');
            $table->string('digit_4', 20)->default('C');
            $table->string('digit_5', 20)->default('O');
            $table->string('digit_6', 20)->default('W');
            $table->string('digit_7', 20)->default('M');
            $table->string('digit_8', 20)->default('I');
            $table->string('digit_9', 20)->default('L');
            $table->string('repeat_2', 20)->default('Z');
            $table->string('repeat_3', 20)->default('X');
            $table->string('repeat_4', 20)->default('W');
            $table->string('repeat_5', 20)->default('V');
            $table->string('repeat_6', 20)->default('VI');
            $table->string('repeat_7', 20)->default('VII');
            $table->string('repeat_8', 20)->default('VIII');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_price_settings');
    }
};
