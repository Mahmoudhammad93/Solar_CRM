<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\CalculationRun;
use App\Models\Customer;
use App\Services\QuotationService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $svc = app(QuotationService::class);
        $lines = $data['lines'] ?? [];

        if (($data['proposal_type'] ?? '') === 'hybrid' && ! empty($data['calculation_run_id'])) {
            $run = CalculationRun::query()->find($data['calculation_run_id']);
            if ($run) {
                $lines = $svc->linesFromCalculation($run);
                $data['hybrid_assumptions'] = [
                    'calculation_reference' => $run->reference,
                    'inputs' => $run->inputs,
                    'outputs' => $run->outputs,
                    'assumptions' => $run->assumptions,
                ];
                $data['system_capacity_kw'] = $data['system_capacity_kw'] ?? ($run->outputs['pv_kwp_recommended'] ?? null);
            }
        }

        if (empty($data['customer_state']) && ! empty($data['customer_id'])) {
            $data['customer_state'] = Customer::query()->whereKey($data['customer_id'])->value('state');
        }

        $header = $data;
        unset($header['lines']);

        return $svc->create($header, $lines, Filament::auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
