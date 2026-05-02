<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_recent_question_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();

            $table->timestamp('last_answered_at');
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'last_answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_recent_question_history');
    }
};

