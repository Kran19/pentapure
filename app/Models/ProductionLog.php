<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'output_product_id', 'output_grade', 'output_qty', 'notes'
    ];

    protected $casts = [
        'output_qty' => 'decimal:3'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The target product that was produced
    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }

    // All raw materials consumed during this production run
    public function inputs(): HasMany
    {
        return $this->hasMany(ProductionLogInput::class);
    }

    protected static function booted()
    {
        static::created(function ($log) {
            $admins = \App\Models\User::where('role', 'ADMIN')->get();
            $productName = $log->outputProduct ? $log->outputProduct->formatName($log->output_grade) : 'Unknown';
            $message = "{$log->user->name} logged production: {$log->output_qty}kg of {$productName}";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\UserActivityNotification('Production Update', $message));
            }
        });
    }
}
