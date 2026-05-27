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
        Schema::table('intents', function (Blueprint $table) {
            $table->string('resource_link')->nullable()->after('is_active');
            $table->string('source')->nullable()->after('is_active');
            $table->string('reference_in_original_file')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->dropColumn('resource_link');
            $table->dropColumn('source');
            $table->dropColumn('reference_in_original_file');
        });
    }
};
