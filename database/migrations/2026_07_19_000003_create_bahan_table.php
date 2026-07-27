<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Master Bahan (master data barang). Table name preserved from the
        // original Flask project. `kode` is the unique, lowercase key used
        // across the app (also as a column name in stok_masuk/update_stok).
        Schema::create('bahan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode', 50)->unique();
            $table->string('nama', 100);
            $table->string('kategori', 50)->default('Lainnya');
            $table->string('kelompok', 50)->default('Lainnya');
            $table->string('satuan', 10)->default('pcs');
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan');
    }
};