<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['terminal_settings', 'vendista_terminals', 'vendista_transactions', 'warehouses'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropTimestamps();
            });
        }
    }

    public function down(): void
    {
        $tables = ['terminal_settings', 'vendista_terminals', 'vendista_transactions', 'warehouses'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamps();
            });
        }
    }
};
