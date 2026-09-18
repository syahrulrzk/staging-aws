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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_pks_id')->constrained('master_pks')->onDelete('cascade');
            $table->enum('action', ['create', 'update', 'delete', 'sync'])->comment('Jenis aksi sync');
            $table->enum('status', ['success', 'failed'])->comment('Status aksi');
            $table->json('old_data')->nullable()->comment('Data sebelum perubahan');
            $table->json('new_data')->nullable()->comment('Data sesudah perubahan');
            $table->text('error_message')->nullable()->comment('Pesan error jika gagal');
            $table->timestamp('synced_at')->comment('Waktu sync dilakukan');
            $table->timestamps();

            // Index untuk pencarian
            $table->index('master_pks_id');
            $table->index('action');
            $table->index('status');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
