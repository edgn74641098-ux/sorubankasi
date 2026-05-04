<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_submitted_questions', function (Blueprint $table) {
            $table->string('submission_type')->default('question_suggestion')->after('subject_id');
            $table->foreignId('reported_question_id')
                ->nullable()
                ->after('submission_type')
                ->constrained('questions')
                ->nullOnDelete();
            $table->index(['submission_type', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_submitted_questions', function (Blueprint $table) {
            $table->dropIndex(['submission_type', 'status', 'created_at']);
            $table->dropConstrainedForeignId('reported_question_id');
            $table->dropColumn('submission_type');
        });
    }
};
