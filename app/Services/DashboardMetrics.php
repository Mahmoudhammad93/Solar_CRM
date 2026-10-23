<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Visit;
use App\Support\DashboardFilters;
use App\Support\RecordScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All dashboard KPIs and chart series from stored records — never hard-coded.
 */
class DashboardMetrics
{
    /**
     * @return array<string, int|float|null>
     */
    public function cards(DashboardFilters $filters): array
    {
        $hasPayments = Payment::query()->exists();

        return [
            'new_leads' => $this->leadsQuery($filters)->count(),
            'follow_ups_due' => $this->leadsQuery($filters, applyCreatedRange: false)
                ->whereNotNull('next_follow_up_at')
                ->whereDate('next_follow_up_at', now()->toDateString())
                ->where(fn (Builder $q) => $q->whereNull('stage')->orWhereNotIn('stage', ['won', 'lost', 'converted']))
                ->count(),
            'follow_ups_overdue' => $this->leadsQuery($filters, applyCreatedRange: false)
                ->whereNotNull('next_follow_up_at')
                ->whereDate('next_follow_up_at', '<', now()->toDateString())
                ->where(fn (Builder $q) => $q->whereNull('stage')->orWhereNotIn('stage', ['won', 'lost', 'converted']))
                ->count(),
            'scheduled_visits' => $this->visitsQuery($filters)
                ->where(function (Builder $q) {
                    $q->whereIn('status', ['scheduled', 'rescheduled', 'planned'])
                        ->orWhere(fn (Builder $inner) => $inner->whereNull('status')->whereNotNull('scheduled_at')->where('scheduled_at', '>=', now()));
                })
                ->count(),
            'quotes_draft' => $this->quotationsByStatus($filters, 'draft'),
            'quotes_sent' => $this->quotationsByStatus($filters, 'sent'),
            'quotes_accepted' => $this->quotationsByStatus($filters, 'accepted'),
            'active_projects' => $this->projectsQuery($filters)->where('status', 'active')->count(),
            'open_tickets' => $this->ticketsQuery($filters)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'recorded_revenue' => $hasPayments ? (float) $this->paymentsQuery($filters)->sum('amount') : null,
            'outstanding' => $hasPayments ? $this->outstandingTotal($filters) : null,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function leadStagePipeline(DashboardFilters $filters): array
    {
        return $this->leadsQuery($filters)
            ->select('stage', DB::raw('count(*) as aggregate'))
            ->groupBy('stage')
            ->pluck('aggregate', 'stage')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function leadSourceMix(DashboardFilters $filters): array
    {
        return $this->leadsQuery($filters)
            ->select('source', DB::raw('count(*) as aggregate'))
            ->groupBy('source')
            ->pluck('aggregate', 'source')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return array<string, float>
     */
    public function salesByTeamMember(DashboardFilters $filters): array
    {
        return $this->paymentsQuery($filters)
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->join('users', 'orders.owner_id', '=', 'users.id')
            ->select('users.name', DB::raw('sum(payments.amount) as total'))
            ->groupBy('users.id', 'users.name')
            ->pluck('total', 'name')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /**
     * @return array{sent: int, accepted: int, rate: float}
     */
    public function quoteConversion(DashboardFilters $filters): array
    {
        $sent = $this->quotationsByStatus($filters, 'sent') + $this->quotationsByStatus($filters, 'accepted');
        $accepted = $this->quotationsByStatus($filters, 'accepted');

        return [
            'sent_or_accepted' => $sent,
            'accepted' => $accepted,
            'rate' => $sent > 0 ? round(($accepted / $sent) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return list<array{month: string, total: float}>
     */
    public function monthlyRevenue(DashboardFilters $filters): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', paid_on)"
            : "date_format(paid_on, '%Y-%m')";

        $rows = $this->paymentsQuery($filters)
            ->select(DB::raw("{$monthExpr} as ym"), DB::raw('sum(amount) as total'))
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return $rows->map(fn ($r) => [
            'month' => (string) $r->ym,
            'total' => round((float) $r->total, 2),
        ])->all();
    }

    /**
     * @return array<string, int>
     */
    public function projectStatusMix(DashboardFilters $filters): array
    {
        return $this->projectsQuery($filters)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function recentActivity(DashboardFilters $filters, int $limit = 15): Collection
    {
        $q = AuditLog::query()->with('user')->orderByDesc('created_at');
        if ($filters->from) {
            $q->where('created_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $q->where('created_at', '<=', $filters->to);
        }
        if ($filters->teamMemberId) {
            $q->where('user_id', $filters->teamMemberId);
        }
        if ($filters->viewer && RecordScoping::mustScopeToAssigned($filters->viewer)) {
            $q->where('user_id', $filters->viewer->id);
        }

        return $q->limit($limit)->get();
    }

    /**
     * @return Collection<int, Task>
     */
    public function upcomingTasks(DashboardFilters $filters, int $limit = 10): Collection
    {
        $q = Task::query()
            ->with('owner')
            ->where(fn (Builder $inner) => $inner->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']))
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now()->startOfDay())
            ->orderBy('due_at');

        RecordScoping::applyAssignedScope($q, $filters->viewer, 'owner_id');
        if ($filters->teamMemberId) {
            $q->where('owner_id', $filters->teamMemberId);
        }

        return $q->limit($limit)->get();
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function notificationsFor(?User $user, int $limit = 20): Collection
    {
        if (! $user) {
            return collect();
        }

        return UserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function deepLink(string $kpi, DashboardFilters $filters): string
    {
        $params = http_build_query($filters->queryParams($kpi));

        return match ($kpi) {
            'new_leads', 'follow_ups_due', 'follow_ups_overdue' => '/admin/leads?'.$params,
            'scheduled_visits' => '/admin/visits?'.$params,
            'quotes_draft', 'quotes_sent', 'quotes_accepted' => '/admin/quotations?'.$params,
            'active_projects' => '/admin/projects?'.$params,
            'open_tickets' => '/admin/tickets?'.$params,
            'recorded_revenue', 'outstanding' => '/admin/invoices?'.$params,
            default => '/admin',
        };
    }

    private function outstandingTotal(DashboardFilters $filters): float
    {
        $invoiceQuery = Invoice::query()->where('status', '!=', 'void');
        if ($filters->from) {
            $invoiceQuery->whereDate('invoiced_on', '>=', $filters->from);
        }
        if ($filters->to) {
            $invoiceQuery->whereDate('invoiced_on', '<=', $filters->to);
        }
        if ($filters->viewer) {
            $invoiceQuery->whereHas('order', function (Builder $q) use ($filters) {
                RecordScoping::applyAssignedScope($q, $filters->viewer, 'owner_id');
                if ($filters->teamMemberId) {
                    $q->where('owner_id', $filters->teamMemberId);
                }
                if ($filters->branchId) {
                    $q->whereHas('owner', fn (Builder $u) => $u->where('branch_id', $filters->branchId));
                }
            });
        }

        $total = 0.0;
        foreach ($invoiceQuery->withSum('payments as paid_sum', 'amount')->get() as $invoice) {
            $total += max(0, (float) $invoice->grand_total - (float) ($invoice->paid_sum ?? 0));
        }

        return round($total, 2);
    }

    private function leadsQuery(DashboardFilters $filters, bool $applyCreatedRange = true): Builder
    {
        $q = Lead::query();
        RecordScoping::applyAssignedScope($q, $filters->viewer, 'assigned_to');
        if ($applyCreatedRange && $filters->from) {
            $q->where('created_at', '>=', $filters->from);
        }
        if ($applyCreatedRange && $filters->to) {
            $q->where('created_at', '<=', $filters->to);
        }
        if ($filters->branchId) {
            $q->where('branch_id', $filters->branchId);
        }
        if ($filters->teamMemberId) {
            $q->where('assigned_to', $filters->teamMemberId);
        }
        if ($filters->leadSource) {
            $q->where('source', $filters->leadSource);
        }
        if ($filters->status) {
            $q->where('stage', $filters->status);
        }

        return $q;
    }

    private function visitsQuery(DashboardFilters $filters): Builder
    {
        $q = Visit::query();
        RecordScoping::applyAssignedScope($q, $filters->viewer, 'assigned_surveyor_id');
        if ($filters->from) {
            $q->where(function (Builder $inner) use ($filters) {
                $inner->where('scheduled_at', '>=', $filters->from)
                    ->orWhere('created_at', '>=', $filters->from);
            });
        }
        if ($filters->to) {
            $q->where(function (Builder $inner) use ($filters) {
                $inner->where('scheduled_at', '<=', $filters->to)
                    ->orWhere('created_at', '<=', $filters->to);
            });
        }
        if ($filters->teamMemberId) {
            $q->where('assigned_surveyor_id', $filters->teamMemberId);
        }
        if ($filters->branchId) {
            $q->whereHas('surveyor', fn (Builder $u) => $u->where('branch_id', $filters->branchId));
        }

        return $q;
    }

    private function quotationsByStatus(DashboardFilters $filters, string $status): int
    {
        $q = Quotation::query()
            ->whereHas('currentRevision', fn (Builder $r) => $r->where('status', $status));
        RecordScoping::applyAssignedScope($q, $filters->viewer, 'owner_id');
        if ($filters->from) {
            $q->where('created_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $q->where('created_at', '<=', $filters->to);
        }
        if ($filters->teamMemberId) {
            $q->where('owner_id', $filters->teamMemberId);
        }
        if ($filters->branchId) {
            $q->whereHas('owner', fn (Builder $u) => $u->where('branch_id', $filters->branchId));
        }

        return $q->count();
    }

    private function projectsQuery(DashboardFilters $filters): Builder
    {
        $q = Project::query();
        if ($filters->viewer && RecordScoping::mustScopeToAssigned($filters->viewer)) {
            $q->where(function (Builder $inner) use ($filters) {
                $inner->where('sales_user_id', $filters->viewer->id)
                    ->orWhere('manager_id', $filters->viewer->id);
            });
        }
        if ($filters->from) {
            $q->where('created_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $q->where('created_at', '<=', $filters->to);
        }
        if ($filters->teamMemberId) {
            $q->where(function (Builder $inner) use ($filters) {
                $inner->where('manager_id', $filters->teamMemberId)
                    ->orWhere('sales_user_id', $filters->teamMemberId);
            });
        }
        if ($filters->status) {
            $q->where('status', $filters->status);
        }

        return $q;
    }

    private function ticketsQuery(DashboardFilters $filters): Builder
    {
        $q = Ticket::query();
        RecordScoping::applyAssignedScope($q, $filters->viewer, 'assignee_id');
        if ($filters->from) {
            $q->where('opened_at', '>=', $filters->from);
        }
        if ($filters->to) {
            $q->where('opened_at', '<=', $filters->to);
        }
        if ($filters->teamMemberId) {
            $q->where('assignee_id', $filters->teamMemberId);
        }
        if ($filters->status) {
            $q->where('status', $filters->status);
        }

        return $q;
    }

    private function paymentsQuery(DashboardFilters $filters): Builder
    {
        $q = Payment::query();
        if ($filters->from) {
            $q->whereDate('paid_on', '>=', $filters->from);
        }
        if ($filters->to) {
            $q->whereDate('paid_on', '<=', $filters->to);
        }
        if ($filters->viewer || $filters->teamMemberId || $filters->branchId) {
            $q->whereHas('invoice.order', function (Builder $order) use ($filters) {
                RecordScoping::applyAssignedScope($order, $filters->viewer, 'owner_id');
                if ($filters->teamMemberId) {
                    $order->where('owner_id', $filters->teamMemberId);
                }
                if ($filters->branchId) {
                    $order->whereHas('owner', fn (Builder $u) => $u->where('branch_id', $filters->branchId));
                }
            });
        }

        return $q;
    }
}
