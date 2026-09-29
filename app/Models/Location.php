<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'description'];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function dispatchLocations(): HasMany
    {
        return $this->hasMany(DispatchItemLocation::class);
    }
}
