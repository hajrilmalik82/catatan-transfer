<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Ambil nilai pengaturan berdasarkan kunci.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember('setting:'.$key, 3600, function () use ($key, $default) {
            try {
                $record = static::find($key);
                if (! $record) {
                    return $default;
                }

                $decoded = json_decode((string) $record->value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $record->value;
            } catch (\Throwable) {
                return $default;
            }
        });
    }

    /**
     * Simpan nilai pengaturan.
     */
    public static function set(string $key, mixed $value): void
    {
        $encoded = is_array($value) || is_bool($value) || is_numeric($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $encoded]
        );

        Cache::forget('setting:'.$key);
    }
}
