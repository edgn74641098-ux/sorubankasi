<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->char('suggested_correct_option', 1)->nullable()->after('note');
            $table->text('user_message')->nullable()->after('review_note');
        });
    }

    public function down(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->dropColumn(['suggested_correct_option', 'user_message']);
        });
    }
};
