<?php

namespace App\Services\Reports;

use App\Models\Quotation;
use App\Support\RecordScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QuotationReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'quotations';
    }

    public function title(): string
    {
        return 'Quotation report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'number', 'label' => 'Quote'],
            ['key' => 'customer', 'label' => 'Customer'],
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'subtotal', 'label' => 'Subtotal'],
            ['key' => 'tax', 'label' => 'Tax'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'owner', 'label' => 'Owner'],
            ['key' => 'valid_until', 'label' => 'Validity'],
            ['key' => 'converted', 'label' => 'Converted'],
        ];
    }

    public function rows(): Collection
    {
        $q = Quotation::query()->with(['customer', 'owner', 'currentRevision', 'order']);
        RecordScoping::applyAssignedScope($q, $this->filters->viewer, 'owner_id');
        if ($this->filters->from) {
            $q->where('created_at', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $q->where('created_at', '<=', $this->filters->to);
        }
        if ($this->filters->teamMemberId) {
            $q->where('owner_id', $this->filters->teamMemberId);
        }
        if ($this->filters->status) {
            $q->whereHas('currentRevision', fn (Builder $r) => $r->where('status', $this->filters->status));
        }

        return $q->orderByDesc('created_at')->get()->map(function (Quotation $quote) {
            $rev = $quote->currentRevision;
            $totals = $rev?->totals ?? [];
            $tax = (float) (($totals['cgst_amount'] ?? 0) + ($totals['sgst_amount'] ?? 0) + ($totals['igst_amount'] ?? 0));

            return [
                'number' => $quote->number,
                'customer' => $quote->customer?->name,
                'date' => optional($quote->created_at)->toDateString(),
                'subtotal' => (float) ($totals['taxable_value'] ?? $totals['subtotal'] ?? 0),
                'tax' => $tax,
                'total' => (float) ($totals['grand_total'] ?? 0),
                'status' => $rev?->status,
                'owner' => $quote->owner?->name,
                'valid_until' => optional($rev?->valid_until)?->toDateString(),
                'converted' => $quote->order ? 'yes' : 'no',
            ];
        });
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'rows' => $rows->count(),
            'subtotal_sum' => round((float) $rows->sum('subtotal'), 2),
            'tax_sum' => round((float) $rows->sum('tax'), 2),
            'total_sum' => round((float) $rows->sum('total'), 2),
        ];
    }
}
