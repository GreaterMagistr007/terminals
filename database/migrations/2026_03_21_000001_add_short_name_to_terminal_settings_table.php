<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminal_settings', function (Blueprint $table) {
            $table->string('short_name', 100)->nullable()->after('vendista_terminal_id');
        });
    }

    public function down(): void
    {
        Schema::table('terminal_settings', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
