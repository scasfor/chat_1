<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentPhrase extends Model
{
    protected $fillable = [
        'intent_id',
        'phrase',
        'normalized_phrase',
    ];

    protected static function booted(): void
    {
        static::saving(function (IntentPhrase $intentPhrase) {
            $intentPhrase->normalized_phrase = self::normalize($intentPhrase->phrase);
        });
    }

    public static function normalize(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    public function intent()
    {
        return $this->belongsTo(Intent::class);
    }
}
