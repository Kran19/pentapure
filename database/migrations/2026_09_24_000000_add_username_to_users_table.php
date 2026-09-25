<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }

        // Auto-generate unique username for existing users if missing
        $users = User::all();
        $usedUsernames = [];
        foreach ($users as $user) {
            if (empty($user->username)) {
                $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->role ?: $user->name));
                if (empty($base)) {
                    $base = 'user';
                }
                $candidate = $base;
                $counter = 1;
                while (in_array($candidate, $usedUsernames) || User::where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                    $counter++;
                    $candidate = $base . $counter;
                }
                $user->username = $candidate;
                $user->save();
                $usedUsernames[] = $candidate;
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
