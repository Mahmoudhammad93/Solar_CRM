<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\QuotationRevision;
use App\Models\StockBalance;
use Illuminate\Support\Collection;

class ProductTaxReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'product_tax';
    }

    public function title(): string
    {
        return 'Product / tax report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'title', 'label' => 'Product'],
            ['key' => 'available', 'label' => 'Available'],
            ['key' => 'reorder', 'label' => 'Reorder'],
            ['key' => 'low_stock', 'label' => 'Low stock'],
            ['key' => 'gst_rate', 'label' => 'GST %'],
        ];
    }

    public function rows(): Collection
    {
        return Product::query()
            ->where('status', 'active')
            ->orderBy('sku')
            ->get()
            ->map(function (Product $p) {
                $available = (float) StockBalance::query()->where('product_id', $p->id)->sum('available');
                $threshold = (float) ($p->reorder_threshold ?? 0);

                return [
                    'sku' => $p->sku,
                    'title' => $p->title,
                    'available' => $available,
                    'reorder' => $threshold,
                    'low_stock' => $available <= $threshold ? 'yes' : 'no',
                    'gst_rate' => (float) ($p->gst_rate ?? 0),
                ];
            });
    }

    public function totals(): array
    {
        $rows = $this->rows();
        $taxFromQuotes = QuotationRevision::query()
            ->when($this->filters->from, fn ($q) => $q->where('created_at', '>=', $this->filters->from))
            ->when($this->filters->to, fn ($q) => $q->where('created_at', '<=', $this->filters->to))
            ->get()
            ->sum(function (QuotationRevision $r) {
                $t = $r->totals ?? [];

                return (float) (($t['cgst_amount'] ?? 0) + ($t['sgst_amount'] ?? 0) + ($t['igst_amount'] ?? 0));
            });

        return [
            'rows' => $rows->count(),
            'low_stock_count' => $rows->where('low_stock', 'yes')->count(),
            'quote_tax_total' => round((float) $taxFromQuotes, 2),
        ];
    }
}
