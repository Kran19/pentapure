<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function workers()
    {
        return $this->hasMany(Worker::class);
    }
}
