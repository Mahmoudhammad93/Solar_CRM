<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Support\ProjectMilestones;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectMilestoneService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function transition(
        ProjectMilestone $milestone,
        string $toStatus,
        ?User $user = null,
        ?string $reason = null,
    ): ProjectMilestone {
        $from = strtolower((string) $milestone->status);
        $to = strtolower($toStatus);

        if (! ProjectMilestones::canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => "Milestone transition {$from} → {$to} is not allowed.",
            ]);
        }

        if (in_array($to, ['hold', 'exception'], true) && blank($reason)) {
            throw ValidationException::withMessages([
                'hold_reason' => 'A reason is required for hold/exception states.',
            ]);
        }

        return DB::transaction(function () use ($milestone, $from, $to, $user, $reason) {
            $milestone->status = $to;
            $milestone->changed_by = $user?->id;
            if ($to === 'active' && ! $milestone->started_at) {
                $milestone->started_at = now();
            }
            if ($to === 'completed') {
                $milestone->completed_at = now();
            }
            if (in_array($to, ['hold', 'exception'], true)) {
                $milestone->hold_reason = $reason;
            }
            $milestone->save();

            $project = $milestone->project;
            if ($to === 'active') {
                $project->update(['current_milestone_key' => $milestone->milestone_key]);
            }
            if ($to === 'completed') {
                $project->recalculatePercentComplete();
                $next = $project->milestones()
                    ->where('sort_order', '>', $milestone->sort_order)
                    ->where('status', 'pending')
                    ->orderBy('sort_order')
                    ->first();
                if ($next) {
                    $next->update([
                        'status' => 'active',
                        'started_at' => now(),
                        'changed_by' => $user?->id,
                    ]);
                    $project->update(['current_milestone_key' => $next->milestone_key]);
                }
                if ($milestone->milestone_key === 'closed') {
                    $project->update(['status' => 'completed', 'actual_end' => now()->toDateString()]);
                    $project->order?->update(['status' => 'completed']);
                }
            }

            $this->auditLogger->log(
                action: 'project.milestone_changed',
                entityType: Project::class,
                entityId: $project->id,
                entityReference: $project->number,
                summary: [
                    'milestone' => $milestone->milestone_key,
                    'from' => $from,
                    'to' => $to,
                    'reason' => $reason,
                ],
            );

            return $milestone->fresh();
        });
    }
}
