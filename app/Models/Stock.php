<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'stage', 'grade', 'quantity', 'transaction_type', 'notes', 'boxes', 'weight_per_box'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'weight_per_box' => 'decimal:3',
        'boxes' => 'integer'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope: only IN (inward) transactions
    public function scopeInward($query)
    {
        return $query->where('transaction_type', 'IN');
    }

    // Scope: only OUT (outward) transactions
    public function scopeOutward($query)
    {
        return $query->where('transaction_type', 'OUT');
    }

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }
}
