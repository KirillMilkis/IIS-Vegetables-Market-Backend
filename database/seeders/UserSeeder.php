<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;



class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)->create();
        User::factory()->create([
            'username' => 'pidoras',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
        ]);
        User::factory()->create([
            'username' => 'username',
            'password' => bcrypt('password'),
            'role' => 'reg_user',
        ]);
        User::factory()->create([
            'username' => 'moder',
            'password' => bcrypt('moder'),
            'role' => 'moderator',
        ]);

    }
}
