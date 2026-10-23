<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStageHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeadConversionService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Convert a lead to a customer. Idempotent: repeat calls return the same customer.
     */
    public function convert(Lead $lead, ?User $actor = null): Customer
    {
        return DB::transaction(function () use ($lead, $actor) {
            /** @var Lead $locked */
            $locked = Lead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();

            if ($locked->customer_id) {
                return Customer::query()->findOrFail($locked->customer_id);
            }

            if ($locked->isLost()) {
                throw new RuntimeException('Cannot convert a lost lead.');
            }

            $customer = Customer::query()->create([
                'customer_number' => $this->numbering->next('customer'),
                'customer_type' => $locked->customer_type,
                'name' => $locked->name,
                'company_name' => $locked->company_name,
                'contact_person' => $locked->contact_person,
                'mobile' => $locked->mobile,
                'alternate_phone' => $locked->alternate_phone,
                'email' => $locked->email,
                'address' => $locked->address,
                'city' => $locked->city,
                'district' => $locked->district,
                'state' => $locked->state,
                'postal_code' => $locked->postal_code,
                'gps_lat' => $locked->gps_lat,
                'gps_lng' => $locked->gps_lng,
                'branch_id' => $locked->branch_id,
                'notes' => $locked->notes,
                'created_by' => $actor?->id ?? $locked->created_by,
                'updated_by' => $actor?->id,
            ]);

            $fromStage = $locked->stage;
            $locked->customer_id = $customer->id;
            $locked->converted_at = now();
            $locked->stage = 'converted';
            $locked->updated_by = $actor?->id;
            $locked->save();

            LeadStageHistory::query()->create([
                'lead_id' => $locked->id,
                'from_stage' => $fromStage,
                'to_stage' => 'converted',
                'changed_by' => $actor?->id,
                'notes' => 'Lead converted to customer '.$customer->customer_number,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'lead.converted',
                entityType: Lead::class,
                entityId: $locked->id,
                entityReference: $locked->reference,
                summary: [
                    'customer_id' => $customer->id,
                    'customer_number' => $customer->customer_number,
                ],
            );

            return $customer;
        });
    }
}
