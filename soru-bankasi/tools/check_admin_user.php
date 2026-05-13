<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'admin@sorubank.com';
$user = App\Models\User::query()->where('email', $email)->first();

if (! $user) {
    echo "NOT_FOUND\n";
    exit(0);
}

echo 'FOUND id='.$user->id
    .' active='.(int) $user->is_active
    .' role_id='.$user->role_id
    .' verified='.(is_null($user->email_verified_at) ? 0 : 1)
    ."\n";
