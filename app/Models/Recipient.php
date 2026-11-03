<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipient extends Model
{
    protected $fillable = [
        'nama',
        'bank',
        'nomor_rekening',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->nama} ({$this->bank} {$this->nomor_rekening})";
    }
}
