<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('subject_id')->constrained('subjects');

            $table->unsignedSmallInteger('question_count')->default(20);
            $table->unsignedSmallInteger('duration_minutes')->default(30);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->unsignedSmallInteger('score')->default(0); // 0-100
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('blank_count')->default(0);

            $table->string('status'); // active|finished|aborted
            $table->string('feedback_mode'); // INSTANT_FEEDBACK_LOCKED|DELAYED_FEEDBACK|NO_FEEDBACK
            $table->boolean('aborted')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};

