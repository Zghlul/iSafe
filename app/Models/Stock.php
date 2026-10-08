<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_SOLD,
    ];

    public const VARIANT_IBOX = 'ibox';

    public const VARIANT_INTER = 'inter';

    public const VARIANT_OTHER = 'other';

    public const VARIANTS = [
        self::VARIANT_IBOX,
        self::VARIANT_INTER,
        self::VARIANT_OTHER,
    ];

    public const GRADES = ['A', 'B', 'C'];

    public const ACCESSORIES = [
        'box' => 'Dus',
        'charger' => 'Charger',
        'cable' => 'Kabel',
        'earphone' => 'Earphone',
        'warranty_card' => 'Nota/Kartu garansi',
    ];

    protected $fillable = [
        'phone_model_id',
        'storage',
        'color',
        'condition',
        'imei',
        'battery_health',
        'variant',
        'physical_grade',
        'accessories',
        'warranty_until',
        'purchase_date',
        'cost_price',
        'source_name',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'battery_health' => 'integer',
            'accessories' => 'array',
            'warranty_until' => 'date',
            'purchase_date' => 'date',
            'cost_price' => 'integer',
        ];
    }

    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
}
