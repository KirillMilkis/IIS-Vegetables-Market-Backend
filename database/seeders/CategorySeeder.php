<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        $proparent = Category::factory()->create([
            'name' => 'Crop',
            'is_final' => false
        ]);
        $parent1 = Category::factory()->create([
            'name' => 'Vegetables',
            'parent_id' => $proparent->id,
            'is_final' => false
        ]);
        $child = Category::factory()->create([
            'name' => 'Carrot',
            'parent_id' => $parent1->id,
            'is_final' => true
        ]);
        $child = Category::factory()->create([
            'name' => 'Potato',
            'parent_id' => $parent1->id,
            'is_final' => true
        ]);
        $parent2 = Category::factory()->create([
            'name' => 'Fruits',
            'parent_id' => $proparent->id,
            'is_final' => false
        ]);
        $child = Category::factory()->create([
            'name' => 'Apple',
            'parent_id' => $parent2->id,
            'is_final' => true
        ]);
        $child = Category::factory()->create([
            'name' => 'Banana',
            'parent_id' => $parent2->id,
            'is_final' => true
        ]);

        $child = Category::factory()->create([
            'name' => 'Strawberry',
            'parent_id' => 5,
            'is_final' => true,
            'status' => 'PROCESS'
        ]);
    }
}
