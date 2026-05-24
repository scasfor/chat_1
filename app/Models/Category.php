<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'sort_order', 'status'];

    public ?array $topicsBuffer = null;

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->sort_order)) {
                $category->sort_order = (static::max('sort_order') ?? 0) + 1;
            }
        });

        static::saved(function (Category $category) {
            if ($category->topicsBuffer !== null) {
                $category->topics()->delete();
                foreach ($category->topicsBuffer as $topic) {
                    $category->topics()->create(['topic' => $topic]);
                }
                $category->topicsBuffer = null;
            }
        });
    }

    public function topics()
    {
        return $this->hasMany(CategoryTopic::class);
    }
}
