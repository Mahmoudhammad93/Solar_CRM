<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Ticket;
use App\Support\RecordScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MonthlyBusinessReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'monthly';
    }

    public function title(): string
    {
        return 'Monthly business report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'metric', 'label' => 'Metric'],
            ['key' => 'value', 'label' => 'Value'],
        ];
    }

    public function rows(): Collection
    {
        $from = $this->filters->from;
        $to = $this->filters->to;

        $leads = Lead::query();
        RecordScoping::applyAssignedScope($leads, $this->filters->viewer, 'assigned_to');
        $this->applyRange($leads, 'created_at', $from, $to);

        $quotes = Quotation::query();
        RecordScoping::applyAssignedScope($quotes, $this->filters->viewer, 'owner_id');
        $this->applyRange($quotes, 'created_at', $from, $to);
        $accepted = (clone $quotes)->whereHas('currentRevision', fn (Builder $r) => $r->where('status', 'accepted'))->count();
        $quoteCount = (clone $quotes)->count();

        $orders = Order::query();
        RecordScoping::applyAssignedScope($orders, $this->filters->viewer, 'owner_id');
        $this->applyRange($orders, 'created_at', $from, $to);

        $projects = Project::query();
        $this->applyRange($projects, 'created_at', $from, $to);

        $payments = Payment::query();
        if ($from) {
            $payments->whereDate('paid_on', '>=', $from);
        }
        if ($to) {
            $payments->whereDate('paid_on', '<=', $to);
        }

        $outstanding = 0.0;
        foreach (Invoice::query()->where('status', '!=', 'void')->withSum('payments as paid_sum', 'amount')->get() as $inv) {
            $outstanding += max(0, (float) $inv->grand_total - (float) ($inv->paid_sum ?? 0));
        }

        $tickets = Ticket::query()->whereNotIn('status', ['closed', 'resolved']);
        RecordScoping::applyAssignedScope($tickets, $this->filters->viewer, 'assignee_id');

        return collect([
            ['metric' => 'Leads created', 'value' => $leads->count()],
            ['metric' => 'Quotations created', 'value' => $quoteCount],
            ['metric' => 'Quotations accepted', 'value' => $accepted],
            ['metric' => 'Quote conversion %', 'value' => $quoteCount > 0 ? round(($accepted / $quoteCount) * 100, 1) : 0],
            ['metric' => 'Orders created', 'value' => $orders->count()],
            ['metric' => 'Projects created', 'value' => $projects->count()],
            ['metric' => 'Recorded payments', 'value' => round((float) $payments->sum('amount'), 2)],
            ['metric' => 'Outstanding (all open invoices)', 'value' => round($outstanding, 2)],
            ['metric' => 'Open tickets', 'value' => $tickets->count()],
        ]);
    }

    private function applyRange(Builder $q, string $column, $from, $to): void
    {
        if ($from) {
            $q->where($column, '>=', $from);
        }
        if ($to) {
            $q->where($column, '<=', $to);
        }
    }
}
