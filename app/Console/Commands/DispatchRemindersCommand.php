<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Task;
use App\Models\UserNotification;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class DispatchRemindersCommand extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Create in-app notifications for due lead follow-ups and tasks from stored data';

    public function handle(SettingsService $settings): int
    {
        if (! (bool) $settings->get('reminders.channel_in_app', true)) {
            $this->info('In-app reminders disabled.');

            return self::SUCCESS;
        }

        $created = 0;
        $leadWindowHours = (int) $settings->get('reminders.lead_follow_up_hours', 24);
        $taskHoursBefore = (int) $settings->get('reminders.task_due_hours_before', 4);

        $leads = Lead::query()
            ->whereNotNull('next_follow_up_at')
            ->whereNotNull('assigned_to')
            ->where('next_follow_up_at', '<=', now()->addHours(max(0, $leadWindowHours)))
            ->where('next_follow_up_at', '>=', now()->subDay())
            ->where(fn ($q) => $q->whereNull('stage')->orWhereNotIn('stage', ['won', 'lost', 'converted']))
            ->limit(200)
            ->get();

        foreach ($leads as $lead) {
            $exists = UserNotification::query()
                ->where('user_id', $lead->assigned_to)
                ->where('category', 'follow_up')
                ->where('subject_type', Lead::class)
                ->where('subject_id', $lead->id)
                ->whereNull('read_at')
                ->exists();
            if ($exists) {
                continue;
            }
            UserNotification::query()->create([
                'user_id' => $lead->assigned_to,
                'title' => 'Follow-up due: '.$lead->reference,
                'body' => $lead->name.' — '.optional($lead->next_follow_up_at)->toDateTimeString(),
                'category' => 'follow_up',
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'action_url' => '/admin/leads',
                'created_at' => now(),
            ]);
            $created++;
        }

        $tasks = Task::query()
            ->whereNotNull('due_at')
            ->whereNotNull('owner_id')
            ->where('due_at', '<=', now()->addHours(max(0, $taskHoursBefore)))
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']))
            ->limit(200)
            ->get();

        foreach ($tasks as $task) {
            $category = $task->due_at < now() ? 'overdue' : 'task';
            $exists = UserNotification::query()
                ->where('user_id', $task->owner_id)
                ->where('category', $category)
                ->where('subject_type', Task::class)
                ->where('subject_id', $task->id)
                ->whereNull('read_at')
                ->exists();
            if ($exists) {
                continue;
            }
            UserNotification::query()->create([
                'user_id' => $task->owner_id,
                'title' => ($category === 'overdue' ? 'Overdue task: ' : 'Task due: ').$task->title,
                'body' => 'Due '.optional($task->due_at)->toDateTimeString(),
                'category' => $category,
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action_url' => '/admin/tasks',
                'created_at' => now(),
            ]);
            $created++;
        }

        $this->info("Created {$created} in-app reminder notification(s).");

        return self::SUCCESS;
    }
}
