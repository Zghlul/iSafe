<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULT_MIN_STOCK = 'default_min_stock';

    public const OLD_STOCK_DAYS = 'old_stock_days';

    public const ALLOW_MANUAL_SALE = 'allow_manual_sale';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            'settings.'.$key,
            now()->addMinutes(5),
            fn () => static::query()->where('key', $key)->value('value') ?? $default,
        );
    }

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget('settings.'.$setting->key));
        static::deleted(fn (Setting $setting) => Cache::forget('settings.'.$setting->key));
    }
}
