<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneModel extends Model
{
    protected $table = 'phone_models';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];
}
