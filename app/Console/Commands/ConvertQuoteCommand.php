<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use App\Services\ConvertQuotationToProject;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ConvertQuoteCommand extends Command
{
    protected $signature = 'quote:convert {quotation_id}';

    protected $description = 'Convert accepted quotation to order/project (concurrency helper)';

    public function handle(ConvertQuotationToProject $converter): int
    {
        try {
            $quote = Quotation::query()->findOrFail((int) $this->argument('quotation_id'));
            $result = $converter->convert($quote);
            $this->line($result['order']->id.'|'.$result['project']->id.'|'.($result['created'] ? 'created' : 'existing'));

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
