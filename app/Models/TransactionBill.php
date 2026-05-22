<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TransactionBill extends Model
{
    protected $fillable = [
        'transaction_id', 'file_path', 'file_type', 'original_name',
        'mime_type', 'file_size', 'sort_order'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the public URL for this bill file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the absolute disk path (for FPDI/image embedding in PDF).
     */
    public function getAbsolutePathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    /**
     * Whether this is an image file.
     */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * Whether this is a PDF file.
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'pdf';
    }
}
