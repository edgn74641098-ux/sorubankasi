<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Create a database backup file';

    public function handle(): int
    {
        $backupDir = storage_path('backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd_His');
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $source = database_path('database.sqlite');
            $target = $backupDir.DIRECTORY_SEPARATOR."sqlite_{$timestamp}.sqlite";
            File::copy($source, $target);
            $this->info("SQLite backup created: {$target}");

            return self::SUCCESS;
        }

        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', 'localhost');
        $port = (string) config('database.connections.mysql.port', '3306');
        $target = $backupDir.DIRECTORY_SEPARATOR."mysql_{$timestamp}.sql";

        $command = [
            'mysqldump',
            '--host='.$host,
            '--port='.$port,
            '--user='.$user,
            '--password='.$pass,
            $db,
            '--result-file='.$target,
        ];

        $result = Process::run($command);
        if ($result->failed()) {
            $this->error('Database backup failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $this->info("MySQL backup created: {$target}");

        return self::SUCCESS;
    }
}

