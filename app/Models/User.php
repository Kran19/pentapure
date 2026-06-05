<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'parent_id',
        'branch',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'permissions'       => 'array',
        ];
    }

    // Self-referential: Sales user reports to a manager
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    // Activity Relationships
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function dispatchLogs(): HasMany
    {
        return $this->hasMany(DispatchLog::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) return true;
        return in_array($permission, $this->permissions ?? []);
    }
}

