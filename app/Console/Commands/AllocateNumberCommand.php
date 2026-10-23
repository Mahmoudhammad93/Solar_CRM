<?php

namespace App\Console\Commands;

use App\Services\NumberingService;
use Illuminate\Console\Command;

class AllocateNumberCommand extends Command
{
    protected $signature = 'numbering:next {entity=lead}';

    protected $description = 'Allocate the next reference number for an entity (used by concurrency tests)';

    public function handle(NumberingService $numbering): int
    {
        $this->line($numbering->next((string) $this->argument('entity')));

        return self::SUCCESS;
    }
}
