<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->enum('category', [
                'WRONG_ANSWER',
                'UNCLEAR_WORDING',
                'TYPO',
                'OTHER'
            ])->default('OTHER');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'resolved'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            // Indices
            $table->index(['question_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reviewed_by', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_reports');
    }
};
