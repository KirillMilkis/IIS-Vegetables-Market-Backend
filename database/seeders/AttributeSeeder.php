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
        Attribute::factory()->create([
            'name' => 'Price/kg',
            'value_type' => 'PRICE/KG'
        ]);
        Attribute::factory()->create([
            'name' => 'Price/piece',
            'value_type' => 'PRICE/PIECE'
        ]);
        Attribute::factory()->create([
            'name' => 'Original place',
            'value_type' => 'PLACE'
        ]);
        Attribute::factory()->create([
            'name' => 'Quantity',
            'value_type' => 'QUANTITY'
        ]);
        Attribute::factory()->create([
            'name' => 'Expires date',
            'value_type' => 'DATE'
        ]);
       

    }
   
}
