<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchLog extends Model
{
    protected $fillable = ['user_id', 'order_id', 'transporter_id', 'lr_image_path', 'driver_no', 'lr_no'];

    public function dispatchItems(): HasMany
    {
        return $this->hasMany(DispatchLogItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    protected static function booted()
    {
        static::created(function ($log) {
            $admins = \App\Models\User::where('role', 'ADMIN')->get();
            $message = "{$log->user->name} dispatched order #{$log->order_id}";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\UserActivityNotification('Dispatch Alert', $message, route('admin.logs')));
            }
        });
    }
}
