<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->json('suggested_payload_json')->nullable()->after('suggested_subject_id');
        });
    }

    public function down(): void
    {
        Schema::table('question_reports', function (Blueprint $table) {
            $table->dropColumn('suggested_payload_json');
        });
    }
};
