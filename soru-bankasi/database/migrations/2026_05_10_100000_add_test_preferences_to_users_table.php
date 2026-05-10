<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_test_mode', 32)->nullable()->after('is_active');
            $table->unsignedTinyInteger('preferred_min_difficulty')->nullable()->after('preferred_test_mode');
            $table->unsignedTinyInteger('preferred_max_difficulty')->nullable()->after('preferred_min_difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_test_mode',
                'preferred_min_difficulty',
                'preferred_max_difficulty',
            ]);
        });
    }
};
