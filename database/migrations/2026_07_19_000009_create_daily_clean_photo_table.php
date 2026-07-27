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
        // Foto untuk setiap Daily Clean submission.
        // Nama tabel dipertahankan dari Flask.
        Schema::create('daily_clean_photo', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('daily_clean_id')->nullable();
            $table->string('filename', 255)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('daily_clean_id');
            $table->foreign('daily_clean_id')
                ->references('id')
                ->on('daily_clean')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_clean_photo');
    }
};