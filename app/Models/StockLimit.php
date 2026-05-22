<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLimit extends Model
{
    protected $fillable = [
        'product_id', 'stage', 'grade', 'alert_limit'
    ];

    protected $casts = [
        'alert_limit' => 'decimal:3'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
