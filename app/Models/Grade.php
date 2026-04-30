<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
