<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'category', 'note', 'reference'];

    protected $casts = ['amount' => 'decimal:2'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::created(function ($t) {
            $admins = \App\Models\User::where('role', 'ADMIN')->get();
            $message = "{$t->user->name} logged cash {$t->type}: ₹{$t->amount} for {$t->category}";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\UserActivityNotification('Cashier Alert', $message, '/admin/logs'));
            }
        });
    }
}
