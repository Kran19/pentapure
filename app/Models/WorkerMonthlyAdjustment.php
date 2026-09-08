<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerMonthlyAdjustment extends Model
{
    protected $fillable = ['worker_id', 'month', 'petrol_food_amount', 'other_allowance_label', 'advance', 'remark', 'is_paid', 'paid_note', 'paid_at'];
    
    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
