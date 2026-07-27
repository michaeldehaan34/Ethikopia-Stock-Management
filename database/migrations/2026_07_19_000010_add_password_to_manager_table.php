<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom untuk mendukung fitur "Edit Akun Saya" pada Role Manager:
     *   - password       : hash bcrypt (nullable untuk akun lama)
     *   - nama_lengkap   : display name untuk session 'name' (nullable)
     *   - updated_at     : timestamp update (nullable untuk akun lama)
     */
    public function up(): void
    {
        Schema::table('manager', function (Blueprint $table) {
            $table->string('password', 255)->nullable()->after('no_telp');
            $table->string('nama_lengkap', 100)->nullable()->after('password');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manager', function (Blueprint $table) {
            $table->dropColumn(['password', 'nama_lengkap', 'updated_at']);
        });
    }
};

