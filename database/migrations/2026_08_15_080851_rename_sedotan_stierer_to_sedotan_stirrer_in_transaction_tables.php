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
        if (Schema::hasColumn('stok_masuk', 'sedotan_stierer')) {
            Schema::table('stok_masuk', function (Blueprint $table) {
                $table->renameColumn('sedotan_stierer', 'sedotan_stirrer');
            });
        }

        if (Schema::hasColumn('update_stok', 'sedotan_stierer')) {
            Schema::table('update_stok', function (Blueprint $table) {
                $table->renameColumn('sedotan_stierer', 'sedotan_stirrer');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('stok_masuk', 'sedotan_stirrer')) {
            Schema::table('stok_masuk', function (Blueprint $table) {
                $table->renameColumn('sedotan_stirrer', 'sedotan_stierer');
            });
        }

        if (Schema::hasColumn('update_stok', 'sedotan_stirrer')) {
            Schema::table('update_stok', function (Blueprint $table) {
                $table->renameColumn('sedotan_stirrer', 'sedotan_stierer');
            });
        }
    }
};
