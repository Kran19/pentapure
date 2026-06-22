<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'type', 'unit', 'image_url', 'is_active', 'allowed_roles', 'rate', 'threshold'];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_roles' => 'array',
        'rate' => 'float',
        'threshold' => 'float'
    ];

    public function scopeRaw($query)   { return $query->where('type', 'RAW'); }
    public function scopeTarget($query){ return $query; }
    public function scopeActive($query){ return $query->where('is_active', true); }

    public function formatName($grade = null)
    {
        $g = $grade ?: 'N/A';
        $t = strtolower($this->type ?? 'N/A');
        return "{$this->name} - (grade- {$g}) (type - {$t})";
    }

    public function scopeVisibleTo($query, $role)
    {
        if ($role === 'ADMIN') return $query;
        
        return $query->where(function($q) use ($role) {
            $q->whereNull('allowed_roles')
              ->orWhereJsonContains('allowed_roles', $role);
        });
    }

    public function grades()
    {
        return $this->belongsToMany(Grade::class);
    }


    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class, 'output_product_id');
    }

    public function productionInputs(): HasMany
    {
        return $this->hasMany(ProductionLogInput::class, 'input_product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Calculates the current net quantity in stock for this product in a specific stage.
     */
    public function currentStock(string $stage, string $grade = null): float
    {
        $query = $this->stocks()->where('stage', $stage);
        if ($grade) {
            $query->where('grade', $grade);
        }
        return (float) $query->sum(\Illuminate\Support\Facades\DB::raw(
            "CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END"
        ));
    }
}
