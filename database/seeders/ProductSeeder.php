<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Product::factory()->create([
            'name' => 'Red Delicious',
            'category_id' => 6,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.thespruceeats.com%2Fapple-varieties-guide-1135596&psig=AOvVaw3QuGPRMM36_02xC4_d6eqU&ust=1732480873006000&source=images&cd=vfe&opi=89978449&ved=0CBAQjRxqFwoTCNCp8Kmo84kDFQAAAAAdAAAAABAE'
        ]);
        Product::factory()->create([
            'name' => 'Golden Delicious',
            'category_id' => 6,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwaapple.org%2Fvarieties%2Fgolden-delicious%2F&psig=AOvVaw3QuGPRMM36_02xC4_d6eqU&ust=1732480873006000&source=images&cd=vfe&opi=89978449&ved=0CBAQjRxqFwoTCNCp8Kmo84kDFQAAAAAdAAAAABAE'
        ]);
        Product::factory()->create([
            'name' => 'Cavendish Banana',
            'category_id' => 7,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Ftewantinmarketgarden.com%2Fshop%2Ffruit%2Fbanana-cavendish-per-kg%2F&psig=AOvVaw3BFHSAK7MDmHbeb7L0K__c&ust=1732480918583000&source=images&cd=vfe&opi=89978449&ved=0CBAQjRxqFwoTCIjGzcKo84kDFQAAAAAdAAAAABAE'
        ]);
        Product::factory()->create([
            'name' => 'Lady Finger Banana',
            'category_id' => 7,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fgreensmithgrocers.com.au%2Fproduct%2Forganic-lady-finger-bananas%2F&psig=AOvVaw3-UqM_xh9MUeqTKoSf5uOX&ust=1732480961002000&source=images&cd=vfe&opi=89978449&ved=0CBAQjRxqFwoTCJCe2NWo84kDFQAAAAAdAAAAABAE'
        ]);
        Product::factory()->create([
            'name' => 'nantes',
            'category_id' => 3,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.ritchiefeed.com%2Fproducts%2Fscarlet-nantes-carrot-seeds&psig=AOvVaw3JEoiWSh2kmUgPIl3keJ-d&ust=1732481031218000&source=images&cd=vfe&opi=89978449&ved=0CBQQjRxqFwoTCPiAn_qo84kDFQAAAAAdAAAAABAQ'
        ]);
        Product::factory()->create([
            'name' => 'Danvers',
            'category_id' => 3,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fhudsonvalleyseed.com%2Fproducts%2Fdanvers-carrot&psig=AOvVaw1HQlQmfM-6GU7T_bFmW0Zd&ust=1732481082502000&source=images&cd=vfe&opi=89978449&ved=0CBQQjRxqFwoTCOCGt46p84kDFQAAAAAdAAAAABAE'
        ]);
        Product::factory()->create([
            'name' => 'Russet',
            'category_id' => 4,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.thespruceeats.com%2Fpotato-varieties-2215971&psig=AOvVaw3J9Z6'
        ]);
        Product::factory()->create([
            'name' => 'Yukon Gold',
            'category_id' => 4,
            'image_root' => 'https://www.google.com/url?sa=i&url=https%3A%2F%2Fwww.thespruceeats.com%2Fpotato-varieties-2215971&psig=AOvVaw3J9Z6'
        ]);



    }
}
