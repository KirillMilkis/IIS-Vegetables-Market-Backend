<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'parent_id', 'status'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasMany(Category::class, 'parent_id');

    }

    public function allDescendants()
    {
        $descendants = collect();

        foreach ($this->child()->get() as $child)
        {
            // echo "$this->name \n";
            $descendants->push($child);
            $descendants = $descendants->merge($child->allDescendants());
        }
       
        return $descendants;
    }

    public function terminalDescendants()
    {
        return $this->allDescendants()->filter(function ($category) {
            return $category->is_final === true;
        });
    }

}
