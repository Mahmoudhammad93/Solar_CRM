<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class DashboardFilters
{
    public function __construct(
        public readonly ?CarbonInterface $from = null,
        public readonly ?CarbonInterface $to = null,
        public readonly ?int $branchId = null,
        public readonly ?int $teamMemberId = null,
        public readonly ?string $leadSource = null,
        public readonly ?string $status = null,
        public readonly ?User $viewer = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?User $viewer = null): self
    {
        $from = filled($data['startDate'] ?? null) ? Carbon::parse($data['startDate'])->startOfDay() : null;
        $to = filled($data['endDate'] ?? null) ? Carbon::parse($data['endDate'])->endOfDay() : null;

        return new self(
            from: $from,
            to: $to,
            branchId: isset($data['branch_id']) && $data['branch_id'] !== '' ? (int) $data['branch_id'] : null,
            teamMemberId: isset($data['team_member_id']) && $data['team_member_id'] !== '' ? (int) $data['team_member_id'] : null,
            leadSource: filled($data['lead_source'] ?? null) ? (string) $data['lead_source'] : null,
            status: filled($data['status'] ?? null) ? (string) $data['status'] : null,
            viewer: $viewer,
        );
    }

    /**
     * @return array<string, string>
     */
    public function queryParams(string $kpi): array
    {
        return array_filter([
            'kpi' => $kpi,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'branch_id' => $this->branchId,
            'team_member_id' => $this->teamMemberId,
            'lead_source' => $this->leadSource,
            'status' => $this->status,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
