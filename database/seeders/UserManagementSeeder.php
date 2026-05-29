<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserManagementSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'admin',
                'full_name' => 'Administrator',
                'phone' => '0900000001',
                'note' => 'Tài khoản quản trị mặc định',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'username' => 'content.user1',
                'full_name' => 'Nguyễn Văn A',
                'phone' => '0900000002',
                'note' => 'Tài khoản người dùng mẫu 1',
                'role' => 'user',
                'status' => 'active',
            ],
            [
                'username' => 'content.user2',
                'full_name' => 'Trần Thị B',
                'phone' => '0900000003',
                'note' => 'Tài khoản người dùng mẫu 2',
                'role' => 'user',
                'status' => 'active',
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['full_name'],
                    'email' => $account['username'].'@local.internal',
                    'password' => 'admin123456',
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
