<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendista_terminal_id')
                ->unique()
                ->constrained('vendista_terminals')
                ->cascadeOnDelete();
            $table->boolean('hidden')->default(false);
            $table->boolean('uses_water')->default(true);
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_settings');
    }
};
