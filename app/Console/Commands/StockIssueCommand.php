<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class StockIssueCommand extends Command
{
    protected $signature = 'stock:issue {product_id} {warehouse_id} {quantity}';

    protected $description = 'Issue stock from available (concurrency helper)';

    public function handle(StockService $stock): int
    {
        try {
            $movement = $stock->issue(
                (int) $this->argument('product_id'),
                (int) $this->argument('warehouse_id'),
                (float) $this->argument('quantity'),
                null,
                'CLI-ISSUE',
                'Concurrent issue test',
            );
            $this->line((string) $movement->id);

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
