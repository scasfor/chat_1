<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentKeyword extends Model
{
    protected $fillable = [
        'intent_id',
        'keyword',
        'weight'
    ];

    public function intent()
    {
        return $this->belongsTo(Intent::class);
    }
}
