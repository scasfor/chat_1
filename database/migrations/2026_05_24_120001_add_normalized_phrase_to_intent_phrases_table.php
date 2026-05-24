<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intent_phrases', function (Blueprint $table) {
            $table->string('normalized_phrase')->nullable()->index()->after('phrase');
        });
    }

    public function down(): void
    {
        Schema::table('intent_phrases', function (Blueprint $table) {
            $table->dropIndex(['normalized_phrase']);
            $table->dropColumn('normalized_phrase');
        });
    }
};
