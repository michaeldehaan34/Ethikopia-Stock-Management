<?php

namespace Database\Seeders;

use Database\Seeders\BaristaSeeder;
use Database\Seeders\ManagerSeeder;
use Database\Seeders\BahanSeeder;
use Database\Seeders\BahanLimitSeeder;
use Database\Seeders\BahanBakuSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Seeds the role-based accounts (barista & manager) plus the stock
     * master data. No default `users` records are created because the
     * application authenticates against the `barista` / `manager` tables.
     */
    public function run(): void
    {
        $this->call([
            BaristaSeeder::class,
            ManagerSeeder::class,
            BahanSeeder::class,
            BahanLimitSeeder::class,
            BahanBakuSeeder::class,
        ]);
    }
}
