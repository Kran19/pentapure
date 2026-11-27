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
            $hasLogItems = DispatchLogItem::where('order_item_id', $this->id)->exists();
            if ($hasLogItems) {
                $actual = (float) DispatchLogItem::where('order_item_id', $this->id)->sum('quantity');
                if (abs((float)$this->dispatched_qty - $actual) > 0.001) {
                    $this->update(['dispatched_qty' => $actual]);
                    $this->dispatched_qty = $actual;
                }
                return $actual;
            }

            if ($this->order) {
                $orderItemIds = $this->order->relationLoaded('items')
                    ? $this->order->items->pluck('id')->all()
                    : $this->order->items()->pluck('id')->all();

                $orderHasAnyLogItems = !empty($orderItemIds)
                    && DispatchLogItem::whereIn('order_item_id', $orderItemIds)->exists();

                if ($orderHasAnyLogItems) {
                    // Other items in this order have dispatch_log_items, so this item has not been dispatched yet (0)
                    $actual = 0.0;
                    if (abs((float)$this->dispatched_qty - $actual) > 0.001) {
                        $this->update(['dispatched_qty' => $actual]);
                        $this->dispatched_qty = $actual;
                    }
                    return $actual;
                }

                $rawOrderDispStatus = strtoupper(trim((string) $this->order->dispatch_status));
                $isLegacyFullyDispatched = in_array($rawOrderDispStatus, ['DONE', 'COMPLETED', 'FULLY DISPATCHED', 'FULLY_DISPATCHED', 'DISPATCHED'], true)
                    || strtoupper(trim((string) $this->order->status)) === 'CLOSED';

                if ($isLegacyFullyDispatched) {
                    // If parent order is dispatched/closed and no item-level breakdown exists anywhere on the order, it's 100% dispatched
                    $actual = (float) $this->quantity;
                    if (abs((float)$this->dispatched_qty - $actual) > 0.001) {
                        $this->update(['dispatched_qty' => $actual]);
                        $this->dispatched_qty = $actual;
                    }
                    return $actual;
                }

                $hasAnyOrderDispatchLogs = $this->order->relationLoaded('dispatchLogs')
                    ? $this->order->dispatchLogs->isNotEmpty()
                    : $this->order->dispatchLogs()->exists();

                // Only reset to 0 if there are no logs AND the order is not in a dispatched/partial status, AND dispatched_qty wasn't already set
                if (!$hasAnyOrderDispatchLogs && in_array($rawOrderDispStatus, ['', 'PENDING', 'OPEN', 'UNASSIGNED'], true) && (float)$this->dispatched_qty <= 0) {
                    $actual = 0.0;
                    if (abs((float)$this->dispatched_qty - $actual) > 0.001) {
                        $this->update(['dispatched_qty' => $actual]);
                        $this->dispatched_qty = $actual;
                    }
                    return $actual;
                }
            }
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
