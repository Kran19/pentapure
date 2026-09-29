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
        'username',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'parent_id',
        'branch',
        'permissions',
        'visible_cashiers',
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
            'visible_cashiers'  => 'array',
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

    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function attendanceSubmissions(): HasMany
    {
        return $this->hasMany(AttendanceSubmission::class, 'created_by');
    }

    public function getAssociatedDataSummary(): array
    {
        $items = [];
        $stocks = $this->stocks()->count();
        if ($stocks > 0) $items['Stock records'] = $stocks;

        $transactions = $this->transactions()->count();
        if ($transactions > 0) $items['Cashier Transactions'] = $transactions;

        $transactionLogs = $this->transactionLogs()->count();
        if ($transactionLogs > 0) $items['Transaction Logs'] = $transactionLogs;

        $dispatchLogs = $this->dispatchLogs()->count();
        if ($dispatchLogs > 0) $items['Dispatch Logs'] = $dispatchLogs;

        $orders = $this->orders()->count();
        if ($orders > 0) $items['Orders'] = $orders;

        $productionLogs = $this->productionLogs()->count();
        if ($productionLogs > 0) $items['Production Logs'] = $productionLogs;

        $purchaseOrders = $this->purchaseOrders()->count();
        if ($purchaseOrders > 0) $items['Purchase Requests'] = $purchaseOrders;

        $attendanceSubmissions = $this->attendanceSubmissions()->count();
        if ($attendanceSubmissions > 0) $items['Attendance Submissions'] = $attendanceSubmissions;

        $subordinates = $this->subordinates()->count();
        if ($subordinates > 0) $items['Subordinate Users'] = $subordinates;

        return $items;
    }

    public function hasAssociatedData(): bool
    {
        return $this->stocks()->exists()
            || $this->transactions()->exists()
            || $this->transactionLogs()->exists()
            || $this->dispatchLogs()->exists()
            || $this->orders()->exists()
            || $this->productionLogs()->exists()
            || $this->purchaseOrders()->exists()
            || $this->attendanceSubmissions()->exists()
            || $this->subordinates()->exists();
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

