<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Одноразовый фикс существующих данных: Vendista API отдаёт время в Europe/Moscow
 * без TZ-метки, а в БД оно писалось без конвертации (как если бы было UTC).
 * Сдвигаем `time` и `reverse_time` на -3 часа, чтобы привести к реальному UTC.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->migrateSqlite('-3 hours'),
            'mysql', 'mariadb' => $this->migrateMysql('-', 3),
            'pgsql' => $this->migratePgsql('-', 3),
            default => throw new \RuntimeException("Неподдерживаемый драйвер БД: {$driver}"),
        };
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        match ($driver) {
            'sqlite' => $this->migrateSqlite('+3 hours'),
            'mysql', 'mariadb' => $this->migrateMysql('+', 3),
            'pgsql' => $this->migratePgsql('+', 3),
            default => throw new \RuntimeException("Неподдерживаемый драйвер БД: {$driver}"),
        };
    }

    private function migrateSqlite(string $modifier): void
    {
        DB::statement("UPDATE vendista_transactions SET time = datetime(time, ?)", [$modifier]);
        DB::statement(
            "UPDATE vendista_transactions SET reverse_time = datetime(reverse_time, ?) WHERE reverse_time IS NOT NULL",
            [$modifier]
        );
    }

    private function migrateMysql(string $sign, int $hours): void
    {
        $op = $sign === '-' ? 'DATE_SUB' : 'DATE_ADD';
        DB::statement("UPDATE vendista_transactions SET time = {$op}(time, INTERVAL {$hours} HOUR)");
        DB::statement(
            "UPDATE vendista_transactions SET reverse_time = {$op}(reverse_time, INTERVAL {$hours} HOUR) WHERE reverse_time IS NOT NULL"
        );
    }

    private function migratePgsql(string $sign, int $hours): void
    {
        DB::statement("UPDATE vendista_transactions SET time = time {$sign} INTERVAL '{$hours} hours'");
        DB::statement(
            "UPDATE vendista_transactions SET reverse_time = reverse_time {$sign} INTERVAL '{$hours} hours' WHERE reverse_time IS NOT NULL"
        );
    }
};
