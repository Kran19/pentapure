<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchItemLocation extends Model
{
    protected $fillable = ['dispatch_log_item_id', 'location_id', 'quantity', 'stock_id'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function dispatchLogItem(): BelongsTo
    {
        return $this->belongsTo(DispatchLogItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
