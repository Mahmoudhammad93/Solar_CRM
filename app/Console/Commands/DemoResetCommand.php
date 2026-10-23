<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * DEMO ONLY — wipes the database and reloads fictional seed data.
 * Never point this at a production database.
 */
class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Required confirmation flag}';

    protected $description = 'DEMO ONLY: migrate:fresh --seed to restore fictional walkthrough data';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to run without --force (DEMO ONLY destructive reset).');
            $this->line('Usage: php artisan demo:reset --force');

            return self::FAILURE;
        }

        if (app()->environment('production')) {
            $this->error('Blocked in production environment.');

            return self::FAILURE;
        }

        $this->warn('DEMO ONLY — destroying local DB and reseeding fictional data…');
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->info('Demo state restored. See docs/demo-script.md');

        return self::SUCCESS;
    }
}
