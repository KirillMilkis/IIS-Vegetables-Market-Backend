<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'total_price', 'description', 'address', 'status', 'order_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order_product_quantities()
    {
        return $this->hasMany(OrderProductQuantity::class);
    }
}
