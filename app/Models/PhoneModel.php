<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PhoneModel extends Model
{
    protected $table = 'phone_models';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'min_stock',
    ];

    protected function casts(): array
    {
        return ['min_stock' => 'integer'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
