<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'description', 'farmer_id', 'image_root', 'category_id'];

    public function farmer()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute_values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function self_harvestings()
    {
        return $this->hasMany(SelfHarvesting::class);
    }

    public function orders_product_quantity()
    {
        return $this->belongsToMany(OrderProductQuantity::class);
    }


}
