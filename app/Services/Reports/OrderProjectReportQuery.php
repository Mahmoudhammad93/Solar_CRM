<?php

namespace App\Services\Reports;

use App\Models\Project;
use App\Support\RecordScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderProjectReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'orders_projects';
    }

    public function title(): string
    {
        return 'Order / project report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'order', 'label' => 'Order'],
            ['key' => 'project', 'label' => 'Project'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'type', 'label' => 'System type'],
            ['key' => 'capacity', 'label' => 'Capacity kW'],
            ['key' => 'manager', 'label' => 'Manager'],
            ['key' => 'milestone', 'label' => 'Milestone'],
            ['key' => 'planned_end', 'label' => 'Planned end'],
            ['key' => 'actual_end', 'label' => 'Actual end'],
            ['key' => 'pct', 'label' => '% complete'],
        ];
    }

    public function rows(): Collection
    {
        $q = Project::query()->with(['order', 'manager']);
        if ($this->filters->viewer && RecordScoping::mustScopeToAssigned($this->filters->viewer)) {
            $q->where(function (Builder $inner) {
                $inner->where('sales_user_id', $this->filters->viewer->id)
                    ->orWhere('manager_id', $this->filters->viewer->id);
            });
        }
        if ($this->filters->from) {
            $q->where('created_at', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $q->where('created_at', '<=', $this->filters->to);
        }
        if ($this->filters->status) {
            $q->where('status', $this->filters->status);
        }
        if ($this->filters->teamMemberId) {
            $q->where(function (Builder $inner) {
                $inner->where('manager_id', $this->filters->teamMemberId)
                    ->orWhere('sales_user_id', $this->filters->teamMemberId);
            });
        }

        return $q->orderByDesc('id')->get()->map(fn (Project $p) => [
            'order' => $p->order?->number,
            'project' => $p->number,
            'status' => $p->status,
            'type' => $p->order?->project_type,
            'capacity' => (float) ($p->order?->capacity_kw ?? 0),
            'manager' => $p->manager?->name,
            'milestone' => $p->current_milestone_key,
            'planned_end' => optional($p->planned_end)?->toDateString(),
            'actual_end' => optional($p->actual_end)?->toDateString(),
            'pct' => (float) $p->percent_complete,
        ]);
    }
}
