<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'permissions')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Bentuknya {"transaksi": ["lihat","tambah"], "rekap": ["lihat"]}.
            // Diabaikan untuk pengguna ber-role admin, yang selalu penuh.
            $table->json('permissions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
