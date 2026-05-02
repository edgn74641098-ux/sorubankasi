<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions');

            $table->char('user_answer', 1)->nullable(); // A|B|C|D|E|NULL
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedTinyInteger('awarded_points')->default(0); // 0 or 5

            $table->boolean('rollback_applied')->default(false);

            $table->timestamps();

            $table->index(['test_id']);
            $table->index(['question_id']);
            $table->unique(['test_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_items');
    }
};

