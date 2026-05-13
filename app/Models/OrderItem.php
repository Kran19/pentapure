<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'grade', 'quantity', 'dispatched_qty', 'price'];

    protected $casts = ['quantity' => 'decimal:3', 'dispatched_qty' => 'decimal:3', 'price' => 'decimal:2'];

    public function remainingQty(): float
    {
        return (float) $this->quantity - (float) $this->dispatched_qty;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        return (float) ($this->quantity * $this->price);
    }
}
