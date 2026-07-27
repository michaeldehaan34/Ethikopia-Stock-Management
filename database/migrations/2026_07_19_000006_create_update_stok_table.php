<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode bahan (mirrors MASTER_DATA di Flask modules/master_bahan.py).
     * Sama persis dengan tabel stok_masuk agar struktur selaras.
     */
    private function bahanColumns(): array
    {
        return [
            'arabica', 'house_blend', 'galon_amidis', 'fresh_milk', 'uht_milk',
            'condence_milk', 'evaporate_milk', 'black_tea', 'chamomile_tea',
            'peach_tea', 'ice_cube', 'ice_cream', 'syrup_caramel', 'syrup_coconut',
            'syrup_cocopandan', 'syrup_hazelnut', 'syrup_rose', 'syrup_strawberry',
            'syrup_vanilla', 'juice_apple', 'juice_cranberry', 'juice_orange',
            'sauce_lemon', 'sauce_caramel', 'powder_greentea', 'powder_chocolate_milk',
            'powder_red_velvet', 'cocoa_powder', 'tepung_maizena', 'lychee_fruit',
            'gula_kelapa', 'gula_pasir', 'gula_sachet', 'tropicana_slim',
            'oreo_vanilla', 'regal_marie', 'french_fries', 'salt', 'parsley',
            'cooking_oil', 'sauce_hot', 'sauce_tomato', 'cheesecake_original',
            'almond_croissant', 'beef_cheese', 'chocolate_nuts_roll',
            'pain_au_chocolate', 'triple_cheese', 'tabung_gas_elpiji',
            'pure_espresso_machine', 'thermal_paper', 'trash_bag_s', 'trash_bag_m',
            'trash_bag_l', 'pembersih_lantai', 'pembersih_kaca', 'pembersih_piring',
            'pembersih_tangan', 'pembersih_kloset', 'pembersih_kain',
            'pembersih_serangga', 'kamper_toilet', 'spons_cuci', 'pengharum_ruangan',
            'pengharum_otomatis', 'tisu_kotak', 'tisu_bulat', 'sedotan_lancip',
            'sedotan_stierer', 'kantong_plastik_m', 'kantong_plastik_l',
            'plastik_wrap', 'plastik_sarung_tangan', 'plastik_klip',
            'plastik_sealer_cup', 'plastik_cup_14oz', 'paper_cup_8oz',
            'tutup_paper_cup', 'baterai_aa', 'baterai_aaa',
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel transaksi Update Stok. Nama tabel dipertahankan dari Flask.
        Schema::create('update_stok', function (Blueprint $table) {
            $table->increments('id');
            $table->date('tanggal')->nullable();
            $table->string('shift', 20)->nullable();
            $table->string('barista', 100)->nullable();

            foreach ($this->bahanColumns() as $kode) {
                $table->string($kode, 12)->nullable();
            }

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_stok');
    }
};