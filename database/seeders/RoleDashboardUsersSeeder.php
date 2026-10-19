<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class RoleDashboardUsersSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'User Account',
                'email' => 'admin@example.com',
                'system_role' => 'user',
                'password' => config('seeding.user_password'),
            ],
            [
                'name' => 'Admin User',
                'email' => 'superadmin@example.com',
                'system_role' => 'admin',
                'password' => config('seeding.admin_password'),
            ],
        ];

        foreach ($accounts as $account) {
            if (! is_string($account['password']) || strlen($account['password']) < 12) {
                throw new RuntimeException('Set SEED_USER_PASSWORD and SEED_SYSTEM_ADMIN_PASSWORD to passwords of at least 12 characters before seeding.');
            }

            $password = $account['password'];
            unset($account['password']);

            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => $password],
            );

            if ($user->system_role !== $account['system_role']) {
                $user->forceFill(['system_role' => $account['system_role']])->save();
            }
        }
    }
}
