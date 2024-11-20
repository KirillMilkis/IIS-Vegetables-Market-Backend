<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CategoryAttribute extends Model
{
    use HasFactory;

    protected $table = 'category_attribute';
    protected $primaryKey = 'id';
    protected $fillable = ['is_required'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
