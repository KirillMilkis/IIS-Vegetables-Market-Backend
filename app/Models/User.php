<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class User extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'firstname', 'lastname', 'password', 'phone', 'role'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function selfHarvestingsVisits()
    {
        return $this->belongsToMany(SelfHarvesting::class);
    }

    public function selfHarvestingsPlanned()
    {
        return $this->hasMany(SelfHarvesting::class);
    }

    

}
