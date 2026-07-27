<?php

namespace Database\Seeders;

use App\Models\Barista;
use Illuminate\Database\Seeder;

class BaristaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'barista_satu',
                'nama_lengkap' => 'Barista Satu',
                'no_telp' => '081234567890',
                'role' => 'barista',
                'created_at' => now(),
            ],
            [
                'username' => 'barista_dua',
                'nama_lengkap' => 'Barista Dua',
                'no_telp' => '081234567891',
                'role' => 'barista',
                'created_at' => now(),
            ],
        ];

        foreach ($accounts as $account) {
            Barista::updateOrCreate(
                ['username' => $account['username']],
                [
                    'nama_lengkap' => $account['nama_lengkap'],
                    'no_telp' => $account['no_telp'],
                    'role' => $account['role'],
                    'created_at' => $account['created_at'],
                ]
            );
        }
    }
}