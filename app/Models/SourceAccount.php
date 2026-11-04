<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceAccount extends Model
{
    protected $fillable = ['nama', 'nomor_rekening', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->nomor_rekening
            ? "{$this->nama} ({$this->nomor_rekening})"
            : $this->nama;
    }
}
