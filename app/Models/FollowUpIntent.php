<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpIntent extends Model
{
    protected $fillable = [
        'intent_id',
        'follow_up_intent_id'
    ];

    public function intent()
    {
        return $this->belongsTo(Intent::class, 'intent_id');
    }

    public function followUpIntent()
    {
        return $this->belongsTo(Intent::class, 'follow_up_intent_id');
    }
}
