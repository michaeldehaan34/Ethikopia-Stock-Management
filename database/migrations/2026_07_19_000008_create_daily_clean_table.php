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
        // Daily Clean submission (metadata only; photos in daily_clean_photo).
        // Nama tabel dipertahankan dari Flask.
        Schema::create('daily_clean', function (Blueprint $table) {
            $table->increments('id');
            $table->date('tanggal')->nullable();
            $table->string('shift', 20)->nullable();
            $table->unsignedInteger('barista_id')->nullable();
            $table->string('barista', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('barista_id')
                ->references('id')
                ->on('barista')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_clean');
    }
};