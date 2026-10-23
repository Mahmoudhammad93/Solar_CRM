<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use App\Services\DashboardMetrics;
use App\Support\DashboardFilters;
use App\Support\RecordScoping;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DashboardActivityWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Recent activity & upcoming tasks';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $filters = DashboardFilters::fromArray($this->pageFilters ?? [], Filament::auth()->user());
        $metrics = app(DashboardMetrics::class);
        $tasks = $metrics->upcomingTasks($filters, 8);
        $taskSummary = $tasks->isEmpty()
            ? 'Upcoming tasks: none'
            : 'Upcoming tasks: '.$tasks->map(fn ($t) => $t->title.' ('.optional($t->due_at)->toDateString().')')->take(5)->implode(' · ');

        return $table
            ->query(
                AuditLog::query()
                    ->when($filters->from, fn (Builder $q) => $q->where('created_at', '>=', $filters->from))
                    ->when($filters->to, fn (Builder $q) => $q->where('created_at', '<=', $filters->to))
                    ->when(
                        $filters->viewer && RecordScoping::mustScopeToAssigned($filters->viewer),
                        fn (Builder $q) => $q->where('user_id', $filters->viewer->id),
                    )
                    ->orderByDesc('created_at')
                    ->limit(12)
            )
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('action')->badge(),
                TextColumn::make('entity_reference')->label('Ref'),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('summary')->limit(50)->toggleable(),
            ])
            ->paginated(false)
            ->headerActions([])
            ->description($taskSummary);
    }
}
