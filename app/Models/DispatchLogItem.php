<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchLogItem extends Model
{
    protected $fillable = ['dispatch_log_id', 'order_item_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function dispatchLog(): BelongsTo
    {
        return $this->belongsTo(DispatchLog::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
