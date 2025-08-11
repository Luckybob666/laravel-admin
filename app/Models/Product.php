<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    //

    protected $fillable = ['name', 'quantity', 'price', 'total'];

    protected $casts = [
        'quantity' => 'integer',
        'price'    => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function (Product $p) {
            $p->total = (int) $p->quantity * (float) $p->price;
        });
    }
}
