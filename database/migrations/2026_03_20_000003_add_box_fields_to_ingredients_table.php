<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->unsignedInteger('quantity_per_box')->nullable()->after('quantity_per_package');
            $table->decimal('cost_per_unit_in_box', 10, 2)->nullable()->after('quantity_per_box');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['quantity_per_box', 'cost_per_unit_in_box']);
        });
    }
};
