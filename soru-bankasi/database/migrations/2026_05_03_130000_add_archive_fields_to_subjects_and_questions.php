<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_active');
            $table->timestamp('purge_after')->nullable()->after('archived_at');
            $table->index(['archived_at', 'purge_after']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('approved_at');
            $table->timestamp('purge_after')->nullable()->after('archived_at');
            $table->index(['status', 'archived_at', 'purge_after']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['archived_at', 'purge_after']);
            $table->dropColumn(['archived_at', 'purge_after']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['status', 'archived_at', 'purge_after']);
            $table->dropColumn(['archived_at', 'purge_after']);
        });
    }
};
