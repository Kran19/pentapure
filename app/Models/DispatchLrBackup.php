<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchLrBackup extends Model
{
    protected $table = 'dispatch_lr_backups';

    protected $fillable = [
        'dispatch_log_id',
        'file_name',
        'file_path',
        'mime_type',
        'image_base64',
    ];
}
