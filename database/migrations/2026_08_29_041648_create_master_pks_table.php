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
        Schema::create('master_pks', function (Blueprint $table) {
            $table->id();
            $table->string('no_pks', 50)->comment('Nomor PKS');
            $table->string('nama_client', 255)->comment('Nama client berdasarkan PKS');
            $table->string('group_bisnis', 255)->comment('Group bisnis client');
            $table->date('start_date_pks')->comment('Tanggal mulai PKS');
            $table->date('end_date_pks')->comment('Tanggal berakhir PKS');
            $table->string('nama_lob', 100)->comment('Nama Line of Business');
            $table->string('kode_lob', 20)->comment('Kode Line of Business');
            $table->string('jenis_kontrak', 50)->comment('Tipe PKS: New / Addendum / Perpanjangan');
            $table->timestamp('create_at')->nullable()->comment('Waktu data dibuat di BPR');
            $table->timestamp('update_at_bpr')->nullable()->comment('Update terakhir dari BPR');
            $table->timestamp('retry_at')->nullable()->comment('Waktu data disinkronisasi');
            $table->timestamp('sync_at')->nullable()->comment('Waktu terakhir data diperbarui');
            $table->string('sync_status', 20)->default('pending')->comment('Status sinkronisasi: pending / success / failed');
            // Flagging fields
            $table->boolean('is_fetched')->default(false)->comment('Apakah data sudah pernah diambil dari BPR');
            $table->timestamp('fetched_at')->nullable()->comment('Waktu pertama kali data diambil');
            $table->boolean('is_synced')->default(false)->comment('Apakah data sudah pernah di-sync ke AWS');
            $table->timestamp('synced_to_aws_at')->nullable()->comment('Waktu terakhir data di-sync ke AWS');
            $table->integer('fetch_count')->default(0)->comment('Jumlah kali data diambil');
            $table->timestamps();

            // Index untuk pencarian
            $table->index('no_pks');
            $table->index('sync_status');
            $table->index(['no_pks', 'kode_lob'], 'idx_pks_lob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pks');
    }
};
