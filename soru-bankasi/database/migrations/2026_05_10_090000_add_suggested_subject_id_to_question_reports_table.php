<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->foreignId('suggested_subject_id')
                ->nullable()
                ->after('suggested_correct_option')
                ->constrained('subjects')
                ->nullOnDelete();
            $table->index(['suggested_subject_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_subject_id');
        });
    }
};
