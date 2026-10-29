<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, $default = null)
    {
        $cacheKey = "setting.{$key}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $value = static::castValue($setting->value, $setting->type ?? 'string');
        Cache::put($cacheKey, $value, 3600);
        return $value;
    }

    public static function set(string $key, $value, string $type = 'string'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => $type,
            ]
        );

        Cache::forget("setting.{$key}");
        return $setting;
    }

    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean', 'bool' => (bool) $value,
            'integer', 'int'  => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array'   => is_string($value) ? (json_decode($value, true) ?? []) : (array) $value,
            default           => $value,
        };
    }

    protected static function booted()
    {
        static::saved(fn($s) => Cache::forget("setting.{$s->key}"));
        static::deleted(fn($s) => Cache::forget("setting.{$s->key}"));
    }
}
