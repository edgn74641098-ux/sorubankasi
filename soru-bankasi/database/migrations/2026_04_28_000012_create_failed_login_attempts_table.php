<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('ip_address')->nullable()->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['email', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};

