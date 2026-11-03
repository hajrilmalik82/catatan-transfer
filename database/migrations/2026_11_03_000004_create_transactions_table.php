<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('tanggal');
            $table->foreignId('source_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained()->restrictOnDelete();
            $table->string('remark')->nullable();
            // Rupiah penuh sebagai integer, bukan desimal, supaya tidak ada
            // masalah pembulatan.
            $table->unsignedBigInteger('nominal_tagihan');
            $table->unsignedInteger('biaya_admin')->default(0);
            $table->unsignedBigInteger('nominal_transfer');
            $table->string('ref_transaksi')->nullable();
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
