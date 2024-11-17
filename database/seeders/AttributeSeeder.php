<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attribute1 = Attribute::factory()->create([
            'name' => 'Price',
            'is_required' => true,
            'value_type' => 'PRICE/KG'
        ]);
        $attribute2 = Attribute::factory()->create([
            'name' => 'Price',
            'is_required' => true,
            'value_type' => 'PRICE/PIECE'
        ]);
        $attribute3 = Attribute::factory()->create([
            'name' => 'Original place',
            'is_required' => true,
            'value_type' => 'PLACE'
        ]);

        $attribute1->categories()->attach(2);
        $attribute2->categories()->attach(4);
        $attribute3->categories()->attach(1);




    }
   
}
