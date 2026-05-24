<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop the unique constraint so multiple messages per session can be logged
            $table->dropUnique(['session_id']);
            $table->text('normalized_message')->nullable()->after('user_message');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('normalized_message');
            $table->unique('session_id');
        });
    }
};
