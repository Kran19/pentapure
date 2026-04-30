<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'worker_id', 'date', 'in_time', 'out_time', 'break_in', 'break_out',
        'total_hours', 'overtime_hours', 'status', 'calculated_wage'
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
