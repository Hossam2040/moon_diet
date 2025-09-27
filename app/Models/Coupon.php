<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'amount', 'min_subtotal', 'max_uses', 'uses_count', 'starts_at', 'ends_at', 'active'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];
}


