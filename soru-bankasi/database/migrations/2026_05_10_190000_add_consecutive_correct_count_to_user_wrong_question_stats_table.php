<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_wrong_question_stats', function (Blueprint $table) {
            $table->unsignedTinyInteger('consecutive_correct_count')
                ->default(0)
                ->after('wrong_count');
        });
    }

    public function down(): void
    {
        Schema::table('user_wrong_question_stats', function (Blueprint $table) {
            $table->dropColumn('consecutive_correct_count');
        });
    }
};

