<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@cameras.test'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('admin123'),
                'role'     => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'aluno@cameras.test'],
            [
                'name'     => 'Aluno Teste',
                'password' => bcrypt('aluno123'),
                'role'     => 'client',
            ]
        );
    }
}
