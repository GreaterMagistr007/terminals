<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Idempotency-ключ для отправки визита: тот же UUID, что в IndexedDB на клиенте.
// Повторная отправка визита с тем же uuid возвращает существующую запись без побочных эффектов
// (создание, фото, ротация, уведомление в Telegram).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            $table->char('client_uuid', 36)->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }
};
