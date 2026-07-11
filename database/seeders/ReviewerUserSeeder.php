<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewerUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'user.reviewer1',
                'full_name' => 'Tài khoản review 1',
                'phone' => '0853675166',
                'note' => 'Tài khoản reviewer 1',
                'role' => 'reviewer',
                'status' => 'active',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['full_name'],
                    'email' => $account['username'].'@local.internal',
                    'password' => 'reviewer123456',
                    'full_name' => $account['full_name'],
                    'phone' => $account['phone'],
                    'note' => $account['note'],
                    'role' => $account['role'],
                    'status' => $account['status'],
                ]
            );
        }
    }
}
