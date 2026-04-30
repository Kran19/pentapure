<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionLogInput extends Model
{
    protected $fillable = [
        'production_log_id', 'input_product_id', 'input_grade', 'quantity'
    ];

    protected $casts = ['quantity' => 'decimal:3'];

    public function productionLog(): BelongsTo
    {
        return $this->belongsTo(ProductionLog::class);
    }

    // The raw material that was consumed
    public function inputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'input_product_id');
    }
}
