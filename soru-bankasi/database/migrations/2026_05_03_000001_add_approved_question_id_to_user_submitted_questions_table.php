<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_submitted_questions', function (Blueprint $table) {
            $table->foreignId('approved_question_id')
                ->nullable()
                ->after('status')
                ->constrained('questions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_submitted_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_question_id');
        });
    }
};
