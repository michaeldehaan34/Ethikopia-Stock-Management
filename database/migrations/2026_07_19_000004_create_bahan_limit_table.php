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
        // Per-bahan stock limit settings (Habis / Tipis / Aman thresholds).
        // `bahan_id` is the primary key AND a foreign key to `bahan`.
        Schema::create('bahan_limit', function (Blueprint $table) {
            $table->unsignedInteger('bahan_id');
            $table->float('limit_habis')->default(0);
            $table->float('limit_tipis')->default(2);

            $table->primary('bahan_id');
            $table->foreign('bahan_id')
                ->references('id')
                ->on('bahan')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_limit');
    }
};