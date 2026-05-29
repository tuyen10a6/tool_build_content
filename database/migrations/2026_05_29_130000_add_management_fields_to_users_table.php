<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('id');
            $table->string('full_name')->nullable()->after('password');
            $table->string('phone')->nullable()->after('full_name');
            $table->text('note')->nullable()->after('phone');
            $table->string('role')->default('user')->after('note');
            $table->string('status')->default('active')->after('role');
        });

        if (DB::table('users')->count() === 0) {
            DB::table('users')->insert([
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@local.internal',
                'password' => Hash::make('admin123456'),
                'full_name' => 'Administrator',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
                'remember_token' => Str::random(10),
            ]);
        }

        $users = DB::table('users')->orderBy('id')->get();

        foreach ($users as $index => $user) {
            $baseUsername = $user->username ?: Str::slug(str_contains((string) $user->email, '@')
                ? strstr((string) $user->email, '@', true)
                : ($user->name ?: 'user-'.$user->id), '_');
            $baseUsername = $baseUsername ?: 'user_'.$user->id;

            $username = $baseUsername;
            $suffix = 1;

            while (DB::table('users')
                ->where('id', '!=', $user->id)
                ->where('username', $username)
                ->exists()) {
                $username = $baseUsername.'_'.$suffix++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $username,
                    'full_name' => $user->full_name ?: $user->name,
                    'role' => $user->role ?: ($index === 0 ? 'admin' : 'user'),
                    'status' => $user->status ?: 'active',
                    'updated_at' => now(),
                ]);
        }

        if (! DB::table('users')->where('role', 'admin')->exists()) {
            DB::table('users')
                ->orderBy('id')
                ->limit(1)
                ->update([
                    'role' => 'admin',
                    'updated_at' => now(),
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'full_name', 'phone', 'note', 'role', 'status']);
        });
    }
};
