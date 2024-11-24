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
            'image_root' => 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcQisTcRVt1u7-IprpoGwAL1A5_YTCOBH6ug3LjLc6QLFgcTMKyzzmlLpSsanhlJ5SOpp-7_5MJ_mpjfjc0M_RY1mQ'
        ]);
        Product::factory()->create([
            'name' => 'Golden Delicious',
            'category_id' => 6,
            'image_root' => 'https://encrypted-tbn2.gstatic.com/images?q=tbn:ANd9GcRtGyiWtBNNHHMpPTw0EMAvrh3X07SDUeAjRgL7uJkShduapLZarwldBguVOaeR4TUNVbihwIhplp1I60x0Nso0jw'
        ]);
        Product::factory()->create([
            'name' => 'Cavendish',
            'category_id' => 7,
            'image_root' => 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcT9UowXH5qO4don2Ve4tSokiCJg5SIV9anBTOdIeprSlRdMwsau5r087WprVKPU7v9yD89iZvn-YYVr4A36Oe1HlQ'
        ]);
        Product::factory()->create([
            'name' => 'Lady Finger',
            'category_id' => 7,
            'image_root' => 'https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcR7UfwkjJ2EXptGih775Y78UGF19NuDONmXesYbNoiww-A51JH9YL7tRc-ED1uGPwk8KTET'
        ]);
        Product::factory()->create([
            'name' => 'Nantes',
            'category_id' => 3,
            'image_root' => 'https://www.ritchiefeed.com/cdn/shop/files/carrot-scarlet-nantes-carotte-scarlet-nantes-tourne-sol-organic-seeds-106066_720x.png?v=1715615826'
        ]);
        Product::factory()->create([
            'name' => 'Danvers',
            'category_id' => 3,
            'image_root' => 'https://www.sandiaseed.com/cdn/shop/products/Organic-Carrot-Seeds-Danvers_6e14089b-306b-41a5-9b85-b283edf5168f.jpg?v=1690240903&width=416'
        ]);
        Product::factory()->create([
            'name' => 'Russet',
            'category_id' => 4,
            'image_root' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlDLVgnqKiTCg8uSTIYNda59XoqQf9gf7sdw&s'
        ]);
        Product::factory()->create([
            'name' => 'Yukon Gold',
            'category_id' => 4,
            'image_root' => 'https://www.kroger.com/product/images/xlarge/front/0000000004727'
        ]);



    }
}
