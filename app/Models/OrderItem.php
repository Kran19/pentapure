<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'grade', 'quantity', 'dispatched_qty', 'price'];

    protected $casts = ['quantity' => 'decimal:3', 'dispatched_qty' => 'decimal:3', 'price' => 'decimal:2'];

    public function remainingQty(): float
    {
        return max(0, (float) $this->quantity - (float) $this->dispatched_qty);
    }

    public function dispatchLogItems(): HasMany
    {
        return $this->hasMany(DispatchLogItem::class, 'order_item_id');
    }

    public function syncDispatchedQty(): float
    {
        try {
            $actual = (float) DispatchLogItem::where('order_item_id', $this->id)->sum('quantity');
            if (abs((float)$this->dispatched_qty - $actual) > 0.001) {
                $this->update(['dispatched_qty' => $actual]);
                $this->dispatched_qty = $actual;
            }
            return $actual;
        } catch (\Throwable $e) {}
        return (float) $this->dispatched_qty;
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
