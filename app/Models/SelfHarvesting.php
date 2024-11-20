<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelfHarvesting extends Model
{
    use HasFactory;

    protected $table = 'self_harvestings';
    protected $primaryKey = 'id';
    protected $fillable = ['farmer_id', 'product_id', 'name', 'description', 'date_time', 'location'];

    public function farmer()
    {
        return $this->belongsTo(User::class);
    }

    public function visitors()
    {
        return $this->belongsToMany(User::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
