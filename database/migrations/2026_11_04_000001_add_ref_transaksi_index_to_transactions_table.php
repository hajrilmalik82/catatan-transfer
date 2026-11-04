<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('transactions', ['ref_transaksi'])) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('ref_transaksi');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['ref_transaksi']);
        });
    }
};
