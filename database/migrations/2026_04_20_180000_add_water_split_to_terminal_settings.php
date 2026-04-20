<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminal_settings', function (Blueprint $table) {
            $table->boolean('water_split')->default(false)->after('uses_water');
        });
    }

    public function down(): void
    {
        Schema::table('terminal_settings', function (Blueprint $table) {
            $table->dropColumn('water_split');
        });
    }
};
