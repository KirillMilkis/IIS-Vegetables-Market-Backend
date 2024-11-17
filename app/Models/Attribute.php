<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'is_required', 'value_type'];

    public function categories()
    {
        return $this->belongsToMany((Category::class));
    }
    public function attribute_values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
