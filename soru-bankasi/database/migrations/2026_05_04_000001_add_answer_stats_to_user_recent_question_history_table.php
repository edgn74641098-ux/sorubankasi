<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_recent_question_history', function (Blueprint $table) {
            $table->unsignedInteger('attempt_count')->default(0)->after('last_answered_at');
            $table->unsignedInteger('correct_count')->default(0)->after('attempt_count');
            $table->unsignedInteger('wrong_count')->default(0)->after('correct_count');

            $table->index(['user_id', 'attempt_count']);
        });
    }

    public function down(): void
    {
        Schema::table('user_recent_question_history', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attempt_count']);
            $table->dropColumn(['attempt_count', 'correct_count', 'wrong_count']);
        });
    }
};
