<?php

namespace Database\Seeders;

use App\Models\Bahan;
use App\Models\BahanLimit;
use Illuminate\Database\Seeder;

class BahanLimitSeeder extends Seeder
{
    /**
     * Default limit (sesuai Flask modules/bahan_limit.py).
     */
    private const DEFAULT_HABIS = 0.0;
    private const DEFAULT_TIPIS = 2.0;

    /**
     * Run the database seeds.
     *
     * Untuk setiap bahan yang BELUM punya entri limit, buat default.
     * Tidak menghapus/mengubah limit yang sudah diatur manager.
     */
    public function run(): void
    {
        $bahanIds = Bahan::pluck('id');

        foreach ($bahanIds as $bahanId) {
            BahanLimit::firstOrCreate(
                ['bahan_id' => $bahanId],
                [
                    'limit_habis' => self::DEFAULT_HABIS,
                    'limit_tipis' => self::DEFAULT_TIPIS,
                ]
            );
        }
    }
}