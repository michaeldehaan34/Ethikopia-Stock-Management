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
        // Token Listrik (input kWh per shift). Nama tabel dipertahankan
        // dari Flask. Database hanya menyimpan nilai numerik (tanpa satuan).
        Schema::create('token_listrik', function (Blueprint $table) {
            $table->increments('id');
            $table->date('tanggal')->nullable();
            $table->string('shift', 20)->nullable();
            $table->unsignedInteger('barista_id')->nullable();
            $table->string('barista', 100)->nullable();
            $table->decimal('token_r17', 10, 2)->nullable();
            $table->decimal('token_r18', 10, 2)->nullable();
            $table->decimal('token_mesin', 10, 2)->nullable();
            $table->text('catatan')->nullable();
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
        Schema::dropIfExists('token_listrik');
    }
};