<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class StockReconcileCommand extends Command
{
    protected $signature = 'stock:reconcile {--repair : Fix balances from ledger sums} {--product=} {--warehouse=}';

    protected $description = 'Compare stock_balances to summed stock_movements deltas';

    public function handle(StockService $stock): int
    {
        $mismatches = $stock->reconcile(
            productId: $this->option('product') ? (int) $this->option('product') : null,
            warehouseId: $this->option('warehouse') ? (int) $this->option('warehouse') : null,
            repair: (bool) $this->option('repair'),
        );

        if ($mismatches === []) {
            $this->info('OK: balances reconcile to ledger.');

            return self::SUCCESS;
        }

        $this->warn('Mismatches: '.count($mismatches));
        foreach ($mismatches as $row) {
            $this->line(json_encode($row));
        }

        return self::FAILURE;
    }
}
