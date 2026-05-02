<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('question_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->text('error_message');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_errors');
    }
};

