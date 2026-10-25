<?php

declare(strict_types=1);

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
        'type',
        'group',
    ];

    /**
     * Get setting value with cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = static::query()->find($key);
            if (! $setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set setting value and invalidate cache.
     */
    public static function set(string $key, mixed $value, ?string $type = null, string $group = 'general'): void
    {
        if ($type === null) {
            $type = match (true) {
                is_int($value) => 'int',
                is_bool($value) => 'bool',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        $storedValue = match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : (string) json_encode($value),
            default => (string) $value,
        };

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("setting:{$key}");
    }

    /**
     * Cast stored string value to appropriate type.
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
