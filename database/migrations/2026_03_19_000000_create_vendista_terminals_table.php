<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendista_terminals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('vendista_id')->unique();
            $table->string('comment')->nullable();
            $table->unsignedInteger('vendista_machine_id')->nullable();
            $table->string('tid')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->dateTime('last_online_at')->nullable();
            $table->unsignedTinyInteger('state')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendista_terminals');
    }
};
