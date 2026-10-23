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
        if (empty($this->file_path)) {
            return '';
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $clean = ltrim(preg_replace('/^(public[\/\\\\]|storage[\/\\\\])+/i', '', trim($this->file_path)), '/\\');

        try {
            return Storage::disk('public')->url($clean);
        } catch (\Throwable $e) {
            return asset('storage/' . $clean);
        }
    }

    /**
     * Locate the file on the physical disk across all possible Laravel storage structures.
     */
    public static function resolveDiskPath(?string $filePath): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        // Direct existing path
        if (file_exists($filePath) && is_file($filePath)) {
            return realpath($filePath) ?: $filePath;
        }

        // Handle URL formatted paths
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            $urlPath = parse_url($filePath, PHP_URL_PATH);
            if ($urlPath) {
                $filePath = $urlPath;
            }
        }

        $clean = ltrim($filePath, '/\\');
        $withoutPrefix = preg_replace('/^(public[\/\\\\]|storage[\/\\\\])+/i', '', $clean);

        $candidates = [
            // Standard Storage disk public
            storage_path('app/public/' . $clean),
            storage_path('app/public/' . $withoutPrefix),
            storage_path('app/' . $clean),
            storage_path('app/' . $withoutPrefix),
            // Public web root locations
            public_path('storage/' . $clean),
            public_path('storage/' . $withoutPrefix),
            public_path($clean),
            public_path($withoutPrefix),
            // Project root / Base path
            base_path($clean),
            base_path($withoutPrefix),
            base_path('storage/app/public/' . $withoutPrefix),
        ];

        try {
            $candidates[] = Storage::disk('public')->path($clean);
            $candidates[] = Storage::disk('public')->path($withoutPrefix);
            $candidates[] = Storage::disk('public')->path($filePath);
        } catch (\Throwable $ignored) {}

        foreach ($candidates as $cand) {
            if (!empty($cand) && file_exists($cand) && is_file($cand)) {
                return realpath($cand) ?: $cand;
            }
        }

        return null;
    }

    /**
     * Get the absolute disk path (for FPDI/image embedding in PDF).
     */
    public function getAbsolutePathAttribute(): string
    {
        $resolved = self::resolveDiskPath($this->file_path);
        if ($resolved) {
            return $resolved;
        }

        try {
            return Storage::disk('public')->path($this->file_path);
        } catch (\Throwable $e) {
            return storage_path('app/public/' . ltrim($this->file_path, '/\\'));
        }
    }

    /**
     * Whether this is an image file.
     */
    public function isImage(): bool
    {
        if (in_array(strtolower((string)$this->file_type), ['image', 'img', 'photo', 'picture'])) {
            return true;
        }

        $path = $this->absolute_path;
        if (!empty($path) && file_exists($path)) {
            $size = @getimagesize($path);
            if ($size && !empty($size[0]) && !empty($size[1])) {
                return true;
            }
        }

        $ext = strtolower(pathinfo($this->file_path ?? '', PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'svg'])) {
            return true;
        }

        $origExt = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        return in_array($origExt, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'svg']);
    }

    /**
     * Whether this is a PDF file.
     */
    public function isPdf(): bool
    {
        if (strtolower((string)$this->file_type) === 'pdf') {
            return true;
        }
        $ext = strtolower(pathinfo($this->file_path ?? '', PATHINFO_EXTENSION));
        return $ext === 'pdf';
    }

    /**
     * Retrieve base64 Data URI for image rendering in DomPDF.
     */
    public function getBase64Attribute(): ?string
    {
        if (!$this->isImage()) {
            return null;
        }

        $abs = $this->absolute_path;
        if (empty($abs) || !file_exists($abs) || !is_file($abs)) {
            return null;
        }

        $data = @file_get_contents($abs);
        if (!$data || strlen($data) === 0) {
            return null;
        }

        $mime = $this->mime_type;
        $size = @getimagesize($abs);
        if ($size && !empty($size['mime'])) {
            $mime = $size['mime'];
        }
        if (empty($mime)) {
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml',
                default => 'image/jpeg',
            };
        }

        // Convert WEBP, BMP, AVIF to standard JPEG using GD for flawless DomPDF rendering
        if (in_array(strtolower($mime), ['image/webp', 'image/avif', 'image/bmp']) && extension_loaded('gd')) {
            $im = @imagecreatefromstring($data);
            if ($im) {
                ob_start();
                imagejpeg($im, null, 90);
                $data = ob_get_clean();
                imagedestroy($im);
                $mime = 'image/jpeg';
            }
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
