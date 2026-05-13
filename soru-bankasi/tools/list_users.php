<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::query()
    ->select(['id', 'name', 'email', 'role_id', 'is_active'])
    ->orderBy('id')
    ->limit(30)
    ->get();

if ($users->isEmpty()) {
    echo "NO_USERS\n";
    exit(0);
}

foreach ($users as $u) {
    echo $u->id.'|'.$u->name.'|'.$u->email.'|role='.$u->role_id.'|active='.(int) $u->is_active."\n";
}
