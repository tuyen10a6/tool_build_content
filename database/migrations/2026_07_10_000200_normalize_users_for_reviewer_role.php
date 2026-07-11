<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email')
            ->orWhere('email', '')
            ->orderBy('id')
            ->get()
            ->each(function ($user): void {
                DB::table('users')->where('id', $user->id)->update([
                    'email' => ($user->username ?: 'user_'.$user->id).'@local.internal',
                    'updated_at' => now(),
                ]);
            });

    }

    public function down(): void
    {
        //
    }
};
