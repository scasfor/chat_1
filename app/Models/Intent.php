<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intent extends Model
{
    protected $fillable = [
        'category_id',
        'intent_key',
        'title',
        'normalized_title',
        'response',
        'priority',
        'is_active',
        'resource_link',
        'source',
        'reference_in_original_file',
    ];

    protected static function booted(): void
    {
        static::saving(function (Intent $intent) {
            if ($intent->isDirty('title')) {
                $intent->normalized_title = IntentPhrase::normalize($intent->title);
            }
        });

        static::saved(function (Intent $intent) {
            if (!$intent->wasChanged('title') && !$intent->wasRecentlyCreated) {
                return;
            }

            $normalizedTitle = IntentPhrase::normalize($intent->title);

            $intent->phrases()
                ->where('normalized_phrase', $normalizedTitle)
                ->first()
                ?? $intent->phrases()->create(['phrase' => $intent->title]);
        });
    }

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
