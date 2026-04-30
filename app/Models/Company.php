<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'gst', 'address', 'contact'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
