<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dispatch_lr_backups')) {
            Schema::create('dispatch_lr_backups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispatch_log_id')->nullable()->index();
                $table->string('file_name', 191)->unique();
                $table->string('file_path', 191);
                $table->string('mime_type', 64)->default('image/jpeg');
                $table->longText('image_base64');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_lr_backups');
    }
};
