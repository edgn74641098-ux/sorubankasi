<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_subject_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedBigInteger('score');
            $table->unsignedInteger('rank');
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['subject_id', 'snapshot_at']);
            $table->index(['user_id', 'snapshot_at']);
            $table->unique(['snapshot_at', 'subject_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_subject_snapshots');
    }
};

