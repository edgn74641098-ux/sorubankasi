<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['status', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['status', 'deleted_at']);
            $table->dropSoftDeletes();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropSoftDeletes();
        });
    }
};
