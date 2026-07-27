<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('bahan', 'sort_order')) {
            Schema::table('bahan', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('urutan');
            });
        }

        $rows = DB::table('bahan')->select('id', 'urutan')->get();
        foreach ($rows as $row) {
            DB::table('bahan')
                ->where('id', $row->id)
                ->update(['sort_order' => (int) ($row->urutan ?? 0)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bahan', 'sort_order')) {
            Schema::table('bahan', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
