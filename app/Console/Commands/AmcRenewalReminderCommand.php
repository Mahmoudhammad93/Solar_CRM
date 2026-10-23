<?php

namespace App\Console\Commands;

use App\Services\AmcService;
use Illuminate\Console\Command;

class AmcRenewalReminderCommand extends Command
{
    protected $signature = 'amc:renewal-reminders {--json : Output JSON}';

    protected $description = 'List AMC contracts due for renewal reminder (daily-command-ready; scheduling automation Partial)';

    public function handle(AmcService $amc): int
    {
        $list = $amc->renewalReminderList();

        if ($this->option('json')) {
            $this->line($list->map(fn ($c) => [
                'number' => $c->number,
                'customer' => $c->customer?->name,
                'ends_on' => $c->ends_on?->toDateString(),
                'renewal_reminder_on' => $c->renewal_reminder_on?->toDateString(),
                'next_service_on' => $c->next_service_on?->toDateString(),
                'status' => $c->status,
            ])->values()->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($list->isEmpty()) {
            $this->info('No AMC contracts due for renewal reminder.');

            return self::SUCCESS;
        }

        $this->table(
            ['Number', 'Customer', 'Ends', 'Reminder', 'Next service', 'Status'],
            $list->map(fn ($c) => [
                $c->number,
                $c->customer?->name,
                $c->ends_on?->toDateString(),
                $c->renewal_reminder_on?->toDateString(),
                $c->next_service_on?->toDateString(),
                $c->status,
            ])->all(),
        );

        $this->comment('Scheduling automation not wired — run this command daily (Partial).');

        return self::SUCCESS;
    }
}
