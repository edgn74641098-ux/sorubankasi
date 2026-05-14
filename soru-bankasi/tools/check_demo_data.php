<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use App\Models\User;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = User::query()->where('email', 'admin@sorubank.com')->first();

if (! $admin) {
    echo "admin_not_found\n";
    exit(1);
}

echo 'admin_id='.$admin->id."\n";
echo 'active_subjects='.Subject::query()->where('is_active', true)->whereNull('deleted_at')->count()."\n";
echo 'admin_active_questions='.Question::query()->where('created_by', $admin->id)->where('status', 'active')->count()."\n";
echo 'admin_finished_tests_last15='
    .Test::query()->where('user_id', $admin->id)->where('status', 'finished')->where('ended_at', '>=', now()->subDays(15))->count()
    ."\n";

