<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('order.number')->label('Order'),
                TextColumn::make('quotation.number')->label('Quote'),
                TextColumn::make('current_milestone_key')->label('Milestone')->badge(),
                TextColumn::make('percent_complete')->suffix('%')->sortable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('planned_end')->date()->toggleable(),
                IconColumn::make('overdue')
                    ->label('Stuck/overdue')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->milestones->contains(fn ($m) => $m->isOverdue())),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
