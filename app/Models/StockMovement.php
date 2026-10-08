<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_SOLD = 'sold';

    public const TYPE_RETURNED = 'returned';

    public const TYPE_REMOVED = 'removed';

    public const TYPES = [
        self::TYPE_IN,
        self::TYPE_SOLD,
        self::TYPE_RETURNED,
        self::TYPE_REMOVED,
    ];

    public $timestamps = false;

    protected $fillable = [
        'stock_id',
        'type',
        'sale_id',
        'moved_at',
        'note',
    ];

    protected function casts(): array
    {
        return ['moved_at' => 'datetime'];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
