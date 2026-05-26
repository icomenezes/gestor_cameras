<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@example.com');
        $name     = env('ADMIN_NAME', 'Administrador');
        $password = env('ADMIN_PASSWORD', 'changeme123');

        User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => bcrypt($password),
                'role'     => 'admin',
            ]
        );
    }
}
