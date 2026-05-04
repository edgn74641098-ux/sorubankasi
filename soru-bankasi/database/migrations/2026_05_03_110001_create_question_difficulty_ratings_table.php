<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_difficulty_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned(); // 1-10
            $table->timestamps();

            // Unique constraint: one rating per user per question
            $table->unique(['user_id', 'question_id']);

            // Indices
            $table->index(['question_id']);
            $table->index(['rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_difficulty_ratings');
    }
};
