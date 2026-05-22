<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add site and description to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('site')->nullable()->after('reference');
            $table->text('description')->nullable()->after('site');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['site', 'description']);
        });
    }
};
