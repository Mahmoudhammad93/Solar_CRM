<?php

namespace App\Services;

use App\Models\AmcContract;
use App\Models\User;
use App\Support\AmcStatuses;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AmcService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): AmcContract
    {
        $reminderDays = (int) $this->settings->get('amc.renewal_reminder_days', 30);
        $endsOn = $data['ends_on'];

        $contract = AmcContract::query()->create([
            'number' => $this->numbering->next('amc'),
            'customer_id' => $data['customer_id'],
            'project_id' => $data['project_id'] ?? null,
            'site_address' => $data['site_address'] ?? null,
            'starts_on' => $data['starts_on'],
            'ends_on' => $endsOn,
            'coverage' => $data['coverage'] ?? null,
            'visit_frequency' => $data['visit_frequency'] ?? 'quarterly',
            'sla_hours' => $data['sla_hours'] ?? (int) $this->settings->get('amc.default_sla_hours', 72),
            'amount' => $data['amount'] ?? 0,
            'next_service_on' => $data['next_service_on'] ?? null,
            'status' => $data['status'] ?? 'active',
            'renewal_reminder_on' => $data['renewal_reminder_on']
                ?? Carbon::parse($endsOn)->subDays($reminderDays)->toDateString(),
            'notes' => $data['notes'] ?? null,
            'owner_id' => $data['owner_id'] ?? $user?->id,
        ]);

        $this->auditLogger->log(
            action: 'amc.created',
            entityType: AmcContract::class,
            entityId: $contract->id,
            entityReference: $contract->number,
            summary: [
                'ends_on' => $contract->ends_on?->toDateString(),
                'renewal_reminder_on' => $contract->renewal_reminder_on?->toDateString(),
            ],
        );

        return $contract;
    }

    public function transition(AmcContract $contract, string $toStatus, ?User $user = null): AmcContract
    {
        $from = strtolower((string) $contract->status);
        $to = strtolower($toStatus);

        if (! AmcStatuses::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "AMC transition {$from} → {$to} is not allowed.",
            ]);
        }

        $contract->status = $to;
        $contract->save();

        $this->auditLogger->log(
            action: 'amc.status_changed',
            entityType: AmcContract::class,
            entityId: $contract->id,
            entityReference: $contract->number,
            summary: ['from' => $from, 'to' => $to, 'user' => $user?->id],
        );

        return $contract->fresh();
    }

    /**
     * Daily-command-ready list of contracts needing renewal attention.
     *
     * @return Collection<int, AmcContract>
     */
    public function renewalReminderList(?CarbonInterface $on = null): Collection
    {
        return AmcContract::query()
            ->with(['customer', 'project'])
            ->dueForRenewalReminder($on)
            ->orderBy('ends_on')
            ->get();
    }
}
