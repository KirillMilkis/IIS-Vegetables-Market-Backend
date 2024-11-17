<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeValue extends Model
{
    use HasFactory;
    protected $table = 'attribute_values';
    protected $primaryKey = 'id';
    protected $fillable = ['value', 'attribute_id', 'product_id'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
    public function products()
    {
        return $this->belongsTo(Product::class);
    }
}
