<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Lookup;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('owner.name')->label('Owner')->sortable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_at')->dateTime()->sortable(),
                TextColumn::make('taskable_type')->label('Link type')->formatStateUsing(fn (?string $state) => class_basename((string) $state))->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_at')
            ->filters([
                SelectFilter::make('status')->options(fn () => Lookup::options('task_status')),
                SelectFilter::make('priority')->options(fn () => Lookup::options('priority')),
                SelectFilter::make('owner_id')->label('Owner')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
