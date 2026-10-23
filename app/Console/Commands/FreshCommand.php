<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Development only: empties all three databases and builds them again.
 * (migrate:fresh only knows about the default connection.)
 */
#[AsCommand(name: 'openrevise:fresh')]
class FreshCommand extends Command
{
    protected $signature = 'openrevise:fresh {--seed : Add the launch boards, subjects and demo content}';

    protected $description = 'Drop every table in the content, admin and analytics databases, then migrate (never use in production)';

    public function handle(): int
    {
        if ($this->laravel->isProduction() && ! $this->confirm('This deletes ALL content, logs and analytics. Continue?')) {
            return self::FAILURE;
        }

        foreach (['content', 'admin', 'analytics'] as $connection) {
            Schema::connection($connection)->dropAllTables();
            $this->components->info("Emptied the $connection database.");
        }

        $this->call('migrate', ['--force' => true]);

        if ($this->option('seed')) {
            $this->call('db:seed', ['--force' => true]);
        }

        return self::SUCCESS;
    }
}
