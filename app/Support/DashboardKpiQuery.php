<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Applies dashboard KPI deep-link query params onto Filament list queries.
 */
final class DashboardKpiQuery
{
    public static function applyLeads(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        self::applyCommonDateAndOwner($query, $request, 'created_at', 'assigned_to', 'branch_id');

        if (filled($request->query('lead_source'))) {
            $query->where('source', $request->query('lead_source'));
        }
        if (filled($request->query('status'))) {
            $query->where('stage', $request->query('status'));
        }

        return match ($request->query('kpi')) {
            'follow_ups_due' => $query
                ->whereNotNull('next_follow_up_at')
                ->whereDate('next_follow_up_at', now()->toDateString())
                ->where(fn (Builder $q) => $q->whereNull('stage')->orWhereNotIn('stage', ['won', 'lost', 'converted'])),
            'follow_ups_overdue' => $query
                ->whereNotNull('next_follow_up_at')
                ->whereDate('next_follow_up_at', '<', now()->toDateString())
                ->where(fn (Builder $q) => $q->whereNull('stage')->orWhereNotIn('stage', ['won', 'lost', 'converted'])),
            default => $query,
        };
    }

    public static function applyVisits(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        if (filled($request->query('from'))) {
            $from = $request->query('from');
            $query->where(function (Builder $inner) use ($from) {
                $inner->where('scheduled_at', '>=', $from)
                    ->orWhere('created_at', '>=', $from);
            });
        }
        if (filled($request->query('to'))) {
            $to = $request->query('to');
            $query->where(function (Builder $inner) use ($to) {
                $inner->where('scheduled_at', '<=', $to.' 23:59:59')
                    ->orWhere('created_at', '<=', $to.' 23:59:59');
            });
        }
        if (filled($request->query('team_member_id'))) {
            $query->where('assigned_surveyor_id', (int) $request->query('team_member_id'));
        }
        if ($request->query('kpi') === 'scheduled_visits') {
            $query->where(function (Builder $q) {
                $q->whereIn('status', ['scheduled', 'rescheduled', 'planned'])
                    ->orWhere(fn (Builder $inner) => $inner->whereNull('status')->whereNotNull('scheduled_at')->where('scheduled_at', '>=', now()));
            });
        }

        return $query;
    }

    public static function applyQuotations(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        self::applyCommonDateAndOwner($query, $request, 'created_at', 'owner_id');

        $status = match ($request->query('kpi')) {
            'quotes_draft' => 'draft',
            'quotes_sent' => 'sent',
            'quotes_accepted' => 'accepted',
            default => filled($request->query('status')) ? (string) $request->query('status') : null,
        };
        if ($status) {
            $query->whereHas('currentRevision', fn (Builder $r) => $r->where('status', $status));
        }

        return $query;
    }

    public static function applyProjects(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        self::applyCommonDateRange($query, $request, 'created_at');
        if (filled($request->query('team_member_id'))) {
            $id = (int) $request->query('team_member_id');
            $query->where(fn (Builder $q) => $q->where('manager_id', $id)->orWhere('sales_user_id', $id));
        }
        if ($request->query('kpi') === 'active_projects') {
            $query->where('status', 'active');
        } elseif (filled($request->query('status'))) {
            $query->where('status', $request->query('status'));
        }

        return $query;
    }

    public static function applyTickets(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        self::applyCommonDateAndOwner($query, $request, 'opened_at', 'assignee_id');
        if ($request->query('kpi') === 'open_tickets') {
            $query->whereNotIn('status', ['resolved', 'closed']);
        } elseif (filled($request->query('status'))) {
            $query->where('status', $request->query('status'));
        }

        return $query;
    }

    public static function applyInvoices(Builder $query, ?Request $request = null): Builder
    {
        $request ??= request();
        self::applyCommonDateRange($query, $request, 'invoiced_on');
        if (filled($request->query('team_member_id'))) {
            $id = (int) $request->query('team_member_id');
            $query->whereHas('order', fn (Builder $o) => $o->where('owner_id', $id));
        }
        if ($request->query('kpi') === 'outstanding') {
            $query->where('status', '!=', 'void');
        }

        return $query;
    }

    private static function applyCommonDateAndOwner(
        Builder $query,
        Request $request,
        string $dateColumn,
        string $ownerColumn,
        ?string $branchColumn = null,
    ): void {
        self::applyCommonDateRange($query, $request, $dateColumn);
        if (filled($request->query('team_member_id'))) {
            $query->where($ownerColumn, (int) $request->query('team_member_id'));
        }
        if ($branchColumn && filled($request->query('branch_id'))) {
            $query->where($branchColumn, (int) $request->query('branch_id'));
        }
    }

    private static function applyCommonDateRange(Builder $query, Request $request, string $dateColumn): void
    {
        if (filled($request->query('from'))) {
            $query->where($dateColumn, '>=', $request->query('from'));
        }
        if (filled($request->query('to'))) {
            $query->where($dateColumn, '<=', $request->query('to').' 23:59:59');
        }
    }
}
