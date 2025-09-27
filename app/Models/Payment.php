<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'provider', 'provider_intent_id', 'status', 'amount', 'currency', 'meta'];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}


