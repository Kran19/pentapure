<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'stage', 'grade', 'location_id', 'quantity', 'transaction_type', 'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:3'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public static function deductStock($productId, $stage, $grade, $quantity, $userId, $notes = '')
    {
        $remaining = $quantity;
        
        $locations = \Illuminate\Support\Facades\DB::table('stocks')
            ->select('location_id', \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN transaction_type = "IN" THEN quantity ELSE -quantity END) as available'))
            ->where('product_id', $productId)
            ->where('stage', $stage)
            ->where('grade', $grade)
            ->whereNotNull('location_id')
            ->groupBy('location_id')
            ->havingRaw('SUM(CASE WHEN transaction_type = "IN" THEN quantity ELSE -quantity END) > 0')
            ->get();
            
        foreach ($locations as $loc) {
            if ($remaining <= 0) break;
            
            $deduct = min($remaining, $loc->available);
            self::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'stage' => $stage,
                'grade' => $grade,
                'location_id' => $loc->location_id,
                'quantity' => $deduct,
                'transaction_type' => 'OUT',
                'notes' => $notes
            ]);
            
            $remaining -= $deduct;
        }
        
        if ($remaining > 0) {
            $defaultLocId = \App\Models\Location::firstOrCreate(['name' => 'Main Warehouse'])->id;
            self::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'stage' => $stage,
                'grade' => $grade,
                'location_id' => $defaultLocId,
                'quantity' => $remaining,
                'transaction_type' => 'OUT',
                'notes' => $notes . ' (Forced deduction fallback)'
            ]);
        }
    }

    // Scope: only IN (inward) transactions
    public function scopeInward($query)
    {
        return $query->where('transaction_type', 'IN');
    }

    // Scope: only OUT (outward) transactions
    public function scopeOutward($query)
    {
        return $query->where('transaction_type', 'OUT');
    }

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }
}
