<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\UserNotification;
use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use App\Support\RecordScoping;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class ActivityCenter extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Activity center';

    protected static ?string $title = 'Activity center';

    protected string $view = 'filament.pages.activity-center';

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(
                UserNotification::query()
                    ->where('user_id', $user?->id)
                    ->orderByDesc('created_at')
            )
            ->columns([
                IconColumn::make('unread')->boolean()->label('Unread')
                    ->getStateUsing(fn (UserNotification $n) => $n->isUnread()),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('category')->badge(),
                TextColumn::make('title')->wrap()->searchable(),
                TextColumn::make('body')->limit(60)->toggleable(),
            ])
            ->recordActions([
                Action::make('markRead')
                    ->visible(fn (UserNotification $r) => $r->isUnread())
                    ->action(function (UserNotification $record): void {
                        $record->markRead();
                        Notification::make()->title('Marked read')->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('refreshReminders')
                    ->label('Sync task reminders')
                    ->action(fn () => $this->syncTaskReminders()),
            ]);
    }

    public function getOverdueTasksProperty()
    {
        $q = Task::query()->overdue()->with('owner');
        RecordScoping::applyAssignedScope($q, Filament::auth()->user(), 'owner_id');

        return $q->limit(20)->get();
    }

    public function getRecentAuditProperty()
    {
        $filters = DashboardFilters::fromArray([], Filament::auth()->user());

        return app(DashboardMetrics::class)->recentActivity($filters, 20);
    }

    protected function syncTaskReminders(): void
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return;
        }

        $tasks = Task::query()->overdue()->where('owner_id', $user->id)->limit(50)->get();
        foreach ($tasks as $task) {
            $exists = UserNotification::query()
                ->where('user_id', $user->id)
                ->where('category', 'overdue')
                ->where('subject_type', Task::class)
                ->where('subject_id', $task->id)
                ->whereNull('read_at')
                ->exists();
            if ($exists) {
                continue;
            }
            UserNotification::query()->create([
                'user_id' => $user->id,
                'title' => 'Overdue task: '.$task->title,
                'body' => 'Due '.optional($task->due_at)->toDateTimeString(),
                'category' => 'overdue',
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'action_url' => '/admin/tasks',
                'created_at' => now(),
            ]);
        }

        Notification::make()->title('Reminders synced')->success()->send();
    }
}
