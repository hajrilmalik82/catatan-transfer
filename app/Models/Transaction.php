<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'uuid',
        'tanggal',
        'source_account_id',
        'recipient_id',
        'remark',
        'nominal_tagihan',
        'biaya_admin',
        'nominal_transfer',
        'ref_transaksi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal_tagihan' => 'integer',
        'biaya_admin' => 'integer',
        'nominal_transfer' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->uuid ??= (string) Str::uuid();
        });

        // Nominal transfer selalu turunan dari tagihan dikurangi admin, supaya
        // tidak pernah ada baris yang ketiga angkanya tidak konsisten.
        static::saving(function (Transaction $transaction) {
            $transaction->nominal_transfer = max(
                0,
                (int) $transaction->nominal_tagihan - (int) $transaction->biaya_admin
            );
        });
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(SourceAccount::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Recipient::class);
    }
}
