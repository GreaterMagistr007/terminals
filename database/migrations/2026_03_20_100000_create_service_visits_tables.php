<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terminal_id')->constrained('vendista_terminals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('visited_at');
            $table->decimal('water_main', 3, 1)->nullable();
            $table->decimal('water_spare', 3, 1)->nullable();
            $table->text('comment')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['terminal_id', 'visited_at']);
        });

        Schema::create('service_visit_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_visit_id')->constrained('service_visits')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('brought')->default(0);
            $table->unsignedInteger('needed')->default(0);
        });

        Schema::create('service_visit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_visit_id')->constrained('service_visits')->cascadeOnDelete();
            $table->string('type', 20); // inside, outside, comment
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_visit_photos');
        Schema::dropIfExists('service_visit_ingredients');
        Schema::dropIfExists('service_visits');
    }
};
