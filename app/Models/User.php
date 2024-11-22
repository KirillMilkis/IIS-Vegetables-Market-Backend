<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'firstname', 'lastname', 'password', 'phone', 'role', 'email', 'address'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'farmer_id');
    }

    public function selfHarvestingsVisits()
    {
        return $this->belongsToMany(SelfHarvesting::class);
    }

    public function selfHarvestingsPlanned()
    {
        return $this->hasMany(SelfHarvesting::class, 'farmer_id');
    }

    

}
