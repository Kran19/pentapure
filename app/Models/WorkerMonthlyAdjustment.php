<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerMonthlyAdjustment extends Model
{
    protected $fillable = ['worker_id', 'month', 'petrol_food_amount', 'advance', 'remark'];
    
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
