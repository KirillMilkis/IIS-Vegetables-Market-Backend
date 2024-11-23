<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AttributeValue;
use App\Models\Product;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $products = Product::all();


        $faker = \Faker\Factory::create();

        foreach ($products as $product) {
            AttributeValue::factory()->create([
                'value' => $faker->numberBetween(1, 1000), 
                'attribute_id' => 1,
                'product_id' => $product->id, 
            ]);
            AttributeValue::factory()->create([
                'value' => $faker->randomElement(['Mogilev', 'Astrakhan', 'Tiraspol']),
                'attribute_id' => 3,
                'product_id' => $product->id,
            ]);
            AttributeValue::factory()->create([
                'value' => $faker->randomElement(['22-03-2025', '22-05-2025', '22-07-2025']),
                'attribute_id' => 5,
                'product_id' => $product->id,
            ]);
            AttributeValue::factory()->create([
                'value' => $faker->numberBetween(1, 1000),
                'attribute_id' => 4,
                'product_id' => $product->id,
            ]);
        }

    }
}
