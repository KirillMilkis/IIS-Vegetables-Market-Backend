<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Attribute extends Model
{
    use HasFactory;

    protected $table = 'attributes';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'value_type'];

    public function attribute_categories()
    {
        return $this->hasMany(CategoryAttribute::class);
    }
    public function attribute_values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
