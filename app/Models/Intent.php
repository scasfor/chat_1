<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intent extends Model
{
    protected $fillable = [
        'category_id',
        'intent_key',
        'title',
        'response',
        'priority',
        'is_active'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function phrases()
    {
        return $this->hasMany(IntentPhrase::class);
    }

    public function keywords()
    {
        return $this->hasMany(IntentKeyword::class);
    }

    public function followUpIntents()
    {
        return $this->belongsToMany(
            Intent::class,
            'follow_up_intents',
            'intent_id',
            'follow_up_intent_id'
        );
    }
}
