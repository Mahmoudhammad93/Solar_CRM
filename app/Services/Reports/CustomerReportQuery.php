<?php

namespace App\Services\Reports;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerReportQuery extends ReportQuery
{
    public function key(): string
    {
        return 'customers';
    }

    public function title(): string
    {
        return 'Customer report';
    }

    public function columns(): array
    {
        return [
            ['key' => 'customer_number', 'label' => 'ID'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'city', 'label' => 'City'],
            ['key' => 'state', 'label' => 'State'],
            ['key' => 'leads', 'label' => 'Leads'],
            ['key' => 'visits', 'label' => 'Visits'],
            ['key' => 'quotes', 'label' => 'Quotes'],
            ['key' => 'orders', 'label' => 'Orders'],
            ['key' => 'tickets', 'label' => 'Tickets'],
        ];
    }

    public function rows(): Collection
    {
        $q = Customer::query()
            ->withCount(['leads', 'visits', 'quotations', 'orders', 'tickets']);
        if ($this->filters->from) {
            $q->where('created_at', '>=', $this->filters->from);
        }
        if ($this->filters->to) {
            $q->where('created_at', '<=', $this->filters->to);
        }
        if ($this->filters->branchId) {
            $q->where('branch_id', $this->filters->branchId);
        }

        return $q->orderBy('name')->get()->map(fn (Customer $c) => [
            'customer_number' => $c->customer_number,
            'name' => $c->name,
            'city' => $c->city,
            'state' => $c->state,
            'leads' => (int) $c->leads_count,
            'visits' => (int) $c->visits_count,
            'quotes' => (int) $c->quotations_count,
            'orders' => (int) $c->orders_count,
            'tickets' => (int) $c->tickets_count,
        ]);
    }
}
