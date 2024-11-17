<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'is_required' => $this->faker->boolean,
            'value_type' => $this->faker->randomElement(['PRICE/KG', 'PRICE/PIECE', 'ORIGINAL PLACE', 'AVAILABLE QUANTITY', 'EXPIRATION DATE', 'WEIGHT']),
        ];
    }
}
