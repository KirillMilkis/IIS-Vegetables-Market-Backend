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
            'name' => 'Plodina',
            'is_final' => false
        ]);
        $parent1 = Category::factory()->create([
            'name' => 'Zelenina',
            'parent_id' => $proparent->id,
            'is_final' => false
        ]);
        $child = Category::factory()->create([
            'name' => 'Mrkev',
            'parent_id' => $parent1->id,
            'is_final' => true
        ]);
        $parent2 = Category::factory()->create([
            'name' => 'Ovoce',
            'parent_id' => $proparent->id,
            'is_final' => false
        ]);
        $child = Category::factory()->create([
            'name' => 'Jablko',
            'parent_id' => $parent2->id,
            'is_final' => true
        ]);
    }
}
