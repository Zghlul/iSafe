<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    public const CONDITION_NEW = 'new';

    public const CONDITION_USED = 'used';

    public const CONDITIONS = [
        self::CONDITION_NEW,
        self::CONDITION_USED,
    ];

    public const PAYMENT_TRANSFER = 'transfer';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_INSTALLMENT = 'installment';

    public const PAYMENT_OTHER = 'other';

    public const PAYMENT_METHODS = [
        self::PAYMENT_TRANSFER,
        self::PAYMENT_CASH,
        self::PAYMENT_INSTALLMENT,
        self::PAYMENT_OTHER,
    ];

    public const STORAGES = [
        '64GB',
        '128GB',
        '256GB',
        '512GB',
        '1TB',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sale_date',
        'stock_id',
        'seller_name',
        'buyer_name',
        'buyer_phone',
        'model',
        'storage',
        'color',
        'condition',
        'imei',
        'selling_price',
        'cost_price',
        'payment_method',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Sale $sale): void {
            $sale->profit = $sale->selling_price - $sale->cost_price;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('imei', 'like', $term)
                        ->orWhere('buyer_name', 'like', $term)
                        ->orWhere('seller_name', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->when(filled($filters['from'] ?? null), fn (Builder $query) => $query->whereDate('sale_date', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn (Builder $query) => $query->whereDate('sale_date', '<=', $filters['to']))
            ->when(filled($filters['model'] ?? null), fn (Builder $query) => $query->where('model', $filters['model']))
            ->when(filled($filters['condition'] ?? null), fn (Builder $query) => $query->where('condition', $filters['condition']))
            ->when(filled($filters['payment_method'] ?? null), fn (Builder $query) => $query->where('payment_method', $filters['payment_method']))
            ->when(filled($filters['seller'] ?? null), fn (Builder $query) => $query->where('seller_name', $filters['seller']));
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'selling_price' => 'integer',
            'cost_price' => 'integer',
            'profit' => 'integer',
        ];
    }
}
