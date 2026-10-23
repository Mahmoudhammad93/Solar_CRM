<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectMilestone;
use App\Services\ProjectMilestoneService;
use App\Support\ProjectMilestones;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Milestones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('weight')->suffix('% wt'),
                TextColumn::make('status')->badge(),
                TextColumn::make('hold_reason')->limit(40)->toggleable(),
                TextColumn::make('due_at')->dateTime(),
                IconColumn::make('overdue')->boolean()->getStateUsing(fn (ProjectMilestone $r) => $r->isOverdue()),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
            ])
            ->recordActions([
                Action::make('transition')
                    ->form([
                        Select::make('status')->options([
                            'active' => 'Active',
                            'completed' => 'Completed',
                            'hold' => 'Hold',
                            'exception' => 'Exception',
                        ])->required(),
                        Textarea::make('reason')->label('Hold/exception reason'),
                    ])
                    ->action(function (ProjectMilestone $record, array $data): void {
                        try {
                            app(ProjectMilestoneService::class)->transition(
                                $record,
                                $data['status'],
                                auth()->user(),
                                $data['reason'] ?? null,
                            );
                            Notification::make()->title('Milestone updated')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('Blocked')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                        }
                    }),
            ]);
    }
}
