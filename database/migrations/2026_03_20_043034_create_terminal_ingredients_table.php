<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendista_terminal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->unique(['vendista_terminal_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_ingredients');
    }
};
