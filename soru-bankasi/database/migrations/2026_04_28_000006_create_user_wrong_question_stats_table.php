<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_wrong_question_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();

            $table->unsignedInteger('wrong_count')->default(0);
            $table->timestamp('last_wrong_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'wrong_count']);
            $table->index(['user_id', 'last_wrong_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wrong_question_stats');
    }
};

