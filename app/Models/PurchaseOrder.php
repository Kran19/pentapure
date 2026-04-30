<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity', 'note', 'status'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The raw material being requested
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    protected static function booted()
    {
        static::created(function ($po) {
            \Log::info('PurchaseOrder Created: notifying admins', ['po_id' => $po->id]);
            $admins = \App\Models\User::where('role', 'ADMIN')->get();
            foreach ($admins as $admin) {
                $message = "{$po->user->name} ({$po->user->role}) requested {$po->quantity}kg of {$po->product->name}";
                \Log::info('Triggering Notification for Admin: ' . $admin->name, ['message' => $message]);
                try {
                    $admin->notify(new \App\Notifications\PurchaseOrderNotification($po->id, $message));
                    \Log::info('Notification handoff successful for: ' . $admin->name);
                } catch (\Exception $e) {
                    \Log::error('Notification handoff failed: ' . $e->getMessage());
                }
            }
        });
    }
}
