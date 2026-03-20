<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Наполнение базы данных начальными данными.
     */
    public function run(): void
    {
        \App\Models\Warehouse::firstOrCreate(
            ['is_default' => true],
            ['name' => 'Дом']
        );
    }
}
