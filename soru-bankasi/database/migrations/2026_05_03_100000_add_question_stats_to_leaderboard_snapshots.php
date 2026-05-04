<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaderboard_global_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('questions_total')->default(0)->after('score');
            $table->unsignedInteger('correct_total')->default(0)->after('questions_total');
            $table->unsignedInteger('wrong_total')->default(0)->after('correct_total');
        });

        Schema::table('leaderboard_subject_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('questions_total')->default(0)->after('score');
            $table->unsignedInteger('correct_total')->default(0)->after('questions_total');
            $table->unsignedInteger('wrong_total')->default(0)->after('correct_total');
        });
    }

    public function down(): void
    {
        Schema::table('leaderboard_global_snapshots', function (Blueprint $table) {
            $table->dropColumn(['questions_total', 'correct_total', 'wrong_total']);
        });

        Schema::table('leaderboard_subject_snapshots', function (Blueprint $table) {
            $table->dropColumn(['questions_total', 'correct_total', 'wrong_total']);
        });
    }
};
