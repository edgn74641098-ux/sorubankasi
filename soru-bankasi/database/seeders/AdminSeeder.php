<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::query()
            ->where('name', 'admin')
            ->value('id');

        if (! $adminRoleId) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@sorubank.com'],
            [
                'role_id' => $adminRoleId,
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'total_score' => 0,
            ]
        );
    }
}