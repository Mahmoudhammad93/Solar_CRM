<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Support\TicketWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): Ticket
    {
        $slaHours = (int) $this->settings->get('tickets.default_sla_hours', 48);
        $opened = now();

        $ticket = Ticket::query()->create([
            'number' => $this->numbering->next('ticket'),
            'customer_id' => $data['customer_id'],
            'project_id' => $data['project_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'category' => $data['category'] ?? null,
            'issue_summary' => $data['issue_summary'],
            'issue_detail' => $data['issue_detail'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'source' => $data['source'] ?? 'internal',
            'opened_at' => $opened,
            'assignee_id' => $data['assignee_id'] ?? null,
            'team_id' => $data['team_id'] ?? null,
            'status' => ! empty($data['assignee_id']) ? 'assigned' : 'open',
            'target_resolution_at' => $data['target_resolution_at']
                ?? $opened->copy()->addHours($slaHours),
            'parts_costs' => $data['parts_costs'] ?? [],
            'created_by' => $user?->id,
        ]);

        TicketStatusHistory::query()->create([
            'ticket_id' => $ticket->id,
            'from_status' => null,
            'to_status' => $ticket->status,
            'notes' => 'Ticket opened',
            'user_id' => $user?->id,
            'created_at' => now(),
        ]);

        $this->auditLogger->log(
            action: 'ticket.created',
            entityType: Ticket::class,
            entityId: $ticket->id,
            entityReference: $ticket->number,
            summary: ['status' => $ticket->status, 'sla_hours' => $slaHours],
        );

        return $ticket->fresh();
    }

    public function transition(Ticket $ticket, string $toStatus, ?User $user = null, ?string $notes = null): Ticket
    {
        $from = strtolower((string) $ticket->status);
        $to = strtolower($toStatus);

        if (! TicketWorkflow::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "Ticket transition {$from} → {$to} is not allowed.",
            ]);
        }

        if ($to === 'assigned' && ! $ticket->assignee_id) {
            throw ValidationException::withMessages([
                'assignee_id' => 'Assignee is required before Assigned status.',
            ]);
        }

        if ($to === 'resolved' && blank($notes) && blank($ticket->resolution_notes)) {
            throw ValidationException::withMessages([
                'resolution_notes' => 'Resolution notes are required to resolve.',
            ]);
        }

        return DB::transaction(function () use ($ticket, $from, $to, $user, $notes) {
            $ticket->status = $to;
            if ($to === 'assigned' || ($to === 'in_progress' && ! $ticket->assignee_id)) {
                // keep assignee
            }
            if ($to === 'resolved') {
                $ticket->resolved_at = now();
                if ($notes) {
                    $ticket->resolution_notes = $notes;
                }
            }
            if ($to === 'closed') {
                $ticket->closed_at = now();
                if (! $ticket->resolved_at) {
                    $ticket->resolved_at = now();
                }
            }
            $ticket->save();

            TicketStatusHistory::query()->create([
                'ticket_id' => $ticket->id,
                'from_status' => $from,
                'to_status' => $to,
                'notes' => $notes,
                'user_id' => $user?->id,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                action: 'ticket.status_changed',
                entityType: Ticket::class,
                entityId: $ticket->id,
                entityReference: $ticket->number,
                summary: ['from' => $from, 'to' => $to],
            );

            return $ticket->fresh();
        });
    }

    public function assign(Ticket $ticket, User $assignee, ?User $actor = null): Ticket
    {
        $ticket->assignee_id = $assignee->id;
        $ticket->save();

        if ($ticket->status === 'open') {
            return $this->transition($ticket->fresh(), 'assigned', $actor, 'Assigned to '.$assignee->name);
        }

        TicketStatusHistory::query()->create([
            'ticket_id' => $ticket->id,
            'from_status' => $ticket->status,
            'to_status' => $ticket->status,
            'notes' => 'Reassigned to '.$assignee->name,
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);

        return $ticket->fresh();
    }

    public function addComment(Ticket $ticket, string $body, ?User $user = null, bool $internal = true): TicketComment
    {
        $comment = TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'body' => $body,
            'is_internal' => $internal,
        ]);

        $this->auditLogger->log(
            action: 'ticket.commented',
            entityType: Ticket::class,
            entityId: $ticket->id,
            entityReference: $ticket->number,
            summary: ['comment_id' => $comment->id],
        );

        return $comment;
    }
}
