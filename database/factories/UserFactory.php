<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    

    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'username' => substr($this->faker->userName, 0, 20),
            'password' => bcrypt('password'),
            'email' => $this->faker->unique()->safeEmail,
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'role' => $this->faker->randomElement(['reg_user', 'moderator', 'admin']),
        ];
    }

    
}
