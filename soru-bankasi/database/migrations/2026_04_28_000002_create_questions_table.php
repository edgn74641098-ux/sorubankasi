<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');

            $table->string('source_type'); // admin|editor|user_submission|import

            $table->text('question_text'); // max 4000 handled by validation
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->text('option_e');
            $table->char('correct_option', 1); // A|B|C|D|E
            $table->text('explanation_text');

            $table->decimal('difficulty_score', 3, 1)->default(5.0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);

            $table->string('status'); // pending|approved|rejected|archived|admin_review
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('current_version')->default(1);

            $table->timestamps();

            $table->index(['subject_id', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['difficulty_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

