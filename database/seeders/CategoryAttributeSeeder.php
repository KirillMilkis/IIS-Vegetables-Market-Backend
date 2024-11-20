<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoryAttribute;

class CategoryAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CategoryAttribute::factory()->create([
            'category_id' => 4,
            'attribute_id' => 2,
            'is_required' => true,
        ]);

        CategoryAttribute::create([
            'category_id' => 1,
            'attribute_id' => 3,
            'is_required' => true,
        ]);
        CategoryAttribute::create([
            'category_id' => 2,
            'attribute_id' => 1,
            'is_required' => true,
        ]);
        CategoryAttribute::create([
            'category_id' => 2,
            'attribute_id' => 2,
            'is_required' => true,
        ]);
        CategoryAttribute::create([
            'category_id' => 2,
            'attribute_id' => 3,
            'is_required' => false,
        ]);
        CategoryAttribute::create([
            'category_id' => 2,
            'attribute_id' => 4,
            'is_required' => true,
        ]);
        CategoryAttribute::create([
            'category_id' => 2,
            'attribute_id' => 5,
            'is_required' => false,
        ]);



    }
   
}