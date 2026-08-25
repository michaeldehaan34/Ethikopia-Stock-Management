<?php

namespace Database\Seeders;

use App\Models\Manager;
use Illuminate\Database\Seeder;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'manager_satu',
                'no_telp' => '081298765432',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'created_at' => now(),
            ],
            [
                'username' => 'manager_dua',
                'no_telp' => '081298765433',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'created_at' => now(),
            ],
        ];

        foreach ($accounts as $account) {
            Manager::updateOrCreate(
                ['username' => $account['username']],
                [
                    'no_telp' => $account['no_telp'],
                    'password' => $account['password'],
                    'created_at' => $account['created_at'],
                ]
            );
        }
    }
}