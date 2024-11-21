<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProductQuantity extends Model
{
    protected $table = 'order_product_quantities';
    protected $primaryKey = 'id';
    protected $fillable = ['order_id', 'product_id', 'quantity', 'status'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
