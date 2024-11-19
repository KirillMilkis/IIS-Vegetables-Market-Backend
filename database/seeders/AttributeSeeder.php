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
            'name' => 'Price/kg',
            'is_required' => true,
            'value_type' => 'PRICE/KG'
        ]);
        $attribute2 = Attribute::factory()->create([
            'name' => 'Price/piece',
            'is_required' => true,
            'value_type' => 'PRICE/PIECE'
        ]);
        $attribute3 = Attribute::factory()->create([
            'name' => 'Original place',
            'is_required' => true,
            'value_type' => 'PLACE'
        ]);
        $attribute4 = Attribute::factory()->create([
            'name' => 'Quantity',
            'is_required' => true,
            'value_type' => 'QUANTITY'
        ]);
        $attribute5 = Attribute::factory()->create([
            'name' => 'Expires date',
            'is_required' => false,
            'value_type' => 'DATE'
        ]);


        $attribute1->categories()->attach(2);
        $attribute2->categories()->attach(4);
        $attribute3->categories()->attach(1);




    }
   
}
