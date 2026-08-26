<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSubmission extends Model
{
    protected $fillable = [
        'attendance_date',
        'status',
        'created_by',
        'submitted_by',
        'submitted_at',
        'last_modified_by',
        'last_modified_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'submitted_at' => 'datetime',
        'last_modified_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function lastModifiedBy()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }
}
