<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'session_id',
        'user_message',
        'normalized_message',
        'intent_id',
        'confidence',
        'bot_response',
    ];

    public function intent()
    {
        return $this->belongsTo(Intent::class);
    }
}
