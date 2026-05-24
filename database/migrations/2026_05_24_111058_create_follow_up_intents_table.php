<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_up_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intent_id')->constrained()->onDelete('cascade');
            $table->foreignId('follow_up_intent_id')->constrained('intents')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_intents');
    }
};
