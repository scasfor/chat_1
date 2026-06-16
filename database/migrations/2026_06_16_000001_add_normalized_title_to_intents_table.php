<?php

use App\Models\Intent;
use App\Models\IntentPhrase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->string('normalized_title')->nullable()->index()->after('title');
        });

        Intent::query()->each(function (Intent $intent) {
            $normalizedTitle = IntentPhrase::normalize($intent->title);

            $intent->updateQuietly([
                'normalized_title' => $normalizedTitle,
            ]);

            if (!$intent->phrases()->where('normalized_phrase', $normalizedTitle)->exists()) {
                $intent->phrases()->create(['phrase' => $intent->title]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->dropIndex(['normalized_title']);
            $table->dropColumn('normalized_title');
        });
    }
};
