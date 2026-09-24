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
        Schema::table('master_pks', function (Blueprint $table) {
            $table->string('code_client', 30)->nullable()->after('nama_client')->comment('Kode client dari BPR');
            $table->string('title', 150)->nullable()->after('jenis_kontrak')->comment('Title dari BPR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pks', function (Blueprint $table) {
            $table->dropColumn(['code_client', 'title']);
        });
    }
};
