<?php

namespace App\Services\Reports;

use App\Models\AmcContract;
use App\Models\Ticket;
use App\Support\RecordScoping;
use Illuminate\Support\Collection;

class TicketAmcReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'tickets_amc';
    }

    public function title(): string
    {
        return 'Ticket / AMC report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'number', 'label' => 'Number'],
            ['key' => 'customer', 'label' => 'Customer'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'detail', 'label' => 'Detail'],
            ['key' => 'date', 'label' => 'Date'],
        ];
    }

    public function rows(): Collection
    {
        $tickets = Ticket::query()->with('customer');
        RecordScoping::applyAssignedScope($tickets, $this->filters->viewer, 'assignee_id');
        if ($this->filters->from) {
            $tickets->where('opened_at', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $tickets->where('opened_at', '<=', $this->filters->to);
        }
        if ($this->filters->status) {
            $tickets->where('status', $this->filters->status);
        }

        $ticketRows = $tickets->orderByDesc('opened_at')->get()->map(fn (Ticket $t) => [
            'type' => 'ticket',
            'number' => $t->number,
            'customer' => $t->customer?->name,
            'status' => $t->status.($t->isOverdue() ? ' (overdue)' : ''),
            'detail' => $t->issue_summary,
            'date' => optional($t->opened_at)?->toDateString(),
        ]);

        $amcRows = AmcContract::query()->with('customer')
            ->when($this->filters->status, fn ($q) => $q->where('status', $this->filters->status))
            ->orderBy('ends_on')
            ->get()
            ->map(fn (AmcContract $c) => [
                'type' => 'amc',
                'number' => $c->number,
                'customer' => $c->customer?->name,
                'status' => $c->status,
                'detail' => 'Next '.optional($c->next_service_on)?->toDateString().' · ends '.optional($c->ends_on)?->toDateString(),
                'date' => optional($c->renewal_reminder_on)?->toDateString(),
            ]);

        return $ticketRows->concat($amcRows)->values();
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'rows' => $rows->count(),
            'tickets' => $rows->where('type', 'ticket')->count(),
            'amc' => $rows->where('type', 'amc')->count(),
        ];
    }
}
