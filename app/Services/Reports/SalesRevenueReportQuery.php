<?php

namespace App\Services\Reports;

use App\Models\Payment;
use App\Support\RecordScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesRevenueReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'sales';
    }

    public function title(): string
    {
        return 'Sales / revenue report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'paid_on', 'label' => 'Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'order', 'label' => 'Order'],
            ['key' => 'owner', 'label' => 'Team member'],
            ['key' => 'method', 'label' => 'Method'],
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'amount', 'label' => 'Amount'],
        ];
    }

    public function rows(): Collection
    {
        $q = Payment::query()->with(['invoice.order.owner', 'invoice']);
        if ($this->filters->from) {
            $q->whereDate('paid_on', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $q->whereDate('paid_on', '<=', $this->filters->to);
        }
        $q->whereHas('invoice.order', function (Builder $order) {
            RecordScoping::applyAssignedScope($order, $this->filters->viewer, 'owner_id');
            if ($this->filters->teamMemberId) {
                $order->where('owner_id', $this->filters->teamMemberId);
            }
        });

        return $q->orderByDesc('paid_on')->get()->map(fn (Payment $p) => [
            'paid_on' => optional($p->paid_on)->toDateString(),
            'invoice' => $p->invoice?->number,
            'order' => $p->invoice?->order?->number,
            'owner' => $p->invoice?->order?->owner?->name,
            'method' => $p->method,
            'reference' => $p->reference,
            'amount' => (float) $p->amount,
        ]);
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'rows' => $rows->count(),
            'recorded_revenue' => round((float) $rows->sum('amount'), 2),
        ];
    }
}
