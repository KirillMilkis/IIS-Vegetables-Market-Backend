<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SelfHarvesting>
 */
class SelfHarvestingFactory extends Factory
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
            'description' => $this->faker->sentence,
            'date_time' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'location' => $this->faker->word,
            'product_id' => \App\Models\Product::inRandomOrder()->first()->id,
            'farmer_id' => \App\Models\User::inRandomOrder()->first()->id,


        ];
    }
}
