<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function getUsageCount(): int
    {
        $nameLower  = strtolower(trim($this->name));
        $slugLower  = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($this->name)));
        $cleanAlnum = strtolower(preg_replace('/[^a-z0-9]/i', '', trim($this->name)));
        $idStr      = (string)$this->id;

        return Transaction::where(function ($q) use ($nameLower, $slugLower, $cleanAlnum, $idStr) {
            $q->whereRaw('LOWER(category) = ?', [$nameLower])
              ->orWhereRaw('LOWER(category) = ?', [$slugLower])
              ->orWhere('category', $idStr);
            if (!empty($cleanAlnum)) {
                $q->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(category, '_', ''), ' ', ''), '&', '')) = ?", [$cleanAlnum]);
            }
        })->count();
    }
}

