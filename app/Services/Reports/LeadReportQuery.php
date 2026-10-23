<?php

namespace App\Services\Reports;

use App\Models\Lead;
use App\Support\RecordScoping;
use Illuminate\Support\Collection;

class LeadReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'leads';
    }

    public function title(): string
    {
        return 'Lead report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'ID'],
            ['key' => 'source', 'label' => 'Source'],
            ['key' => 'owner', 'label' => 'Owner'],
            ['key' => 'stage', 'label' => 'Stage'],
            ['key' => 'created_at', 'label' => 'Created'],
            ['key' => 'next_follow_up_at', 'label' => 'Follow-up'],
            ['key' => 'expected_value', 'label' => 'Expected value'],
            ['key' => 'outcome', 'label' => 'Win/loss'],
        ];
    }

    public function rows(): Collection
    {
        $q = Lead::query()->with('assignee');
        RecordScoping::applyAssignedScope($q, $this->filters->viewer, 'assigned_to');
        if ($this->filters->from) {
            $q->where('created_at', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $q->where('created_at', '<=', $this->filters->to);
        }
        if ($this->filters->leadSource) {
            $q->where('source', $this->filters->leadSource);
        }
        if ($this->filters->status) {
            $q->where('stage', $this->filters->status);
        }
        if ($this->filters->teamMemberId) {
            $q->where('assigned_to', $this->filters->teamMemberId);
        }
        if ($this->filters->branchId) {
            $q->where('branch_id', $this->filters->branchId);
        }

        return $q->orderByDesc('created_at')->get()->map(fn (Lead $l) => [
            'reference' => $l->reference,
            'source' => $l->source,
            'owner' => $l->assignee?->name,
            'stage' => $l->stage,
            'created_at' => optional($l->created_at)->toDateString(),
            'next_follow_up_at' => optional($l->next_follow_up_at)?->toDateTimeString(),
            'expected_value' => (float) ($l->expected_value ?? 0),
            'outcome' => $l->loss_reason ?: ($l->converted_at ? 'won/converted' : ''),
        ]);
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'rows' => $rows->count(),
            'expected_value_sum' => round((float) $rows->sum('expected_value'), 2),
        ];
    }
}
