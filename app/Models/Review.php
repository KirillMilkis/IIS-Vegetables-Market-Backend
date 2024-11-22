<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'product_id', 'rating', 'content'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
