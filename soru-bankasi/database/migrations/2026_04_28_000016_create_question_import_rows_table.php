<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('question_import_batches')->cascadeOnDelete();
            $table->string('question_hash')->index();
            $table->string('action'); // inserted|skipped|merged|manual_review
            $table->foreignId('matched_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_rows');
    }
};

