<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTopic extends Model
{
    protected $fillable = ['category_id', 'topic'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
