<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendista_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trans_id')->unique();
            $table->unsignedBigInteger('term_id')->index();
            $table->integer('sum')->default(0);
            $table->dateTime('time')->index();
            $table->unsignedTinyInteger('result')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedSmallInteger('response_code')->default(0);
            $table->string('card_number', 20)->nullable();
            $table->unsignedBigInteger('reverse_id')->default(0);
            $table->dateTime('reverse_time')->nullable();
            $table->timestamps();

            $table->index(['term_id', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendista_transactions');
    }
};
