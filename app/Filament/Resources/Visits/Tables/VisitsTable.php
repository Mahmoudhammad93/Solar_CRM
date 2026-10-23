<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Models\Lookup;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('lead.reference')->label('Lead')->toggleable(),
                TextColumn::make('scheduled_at')->dateTime()->sortable(),
                TextColumn::make('surveyor.name')->label('Surveyor')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('city')->toggleable(),
                TextColumn::make('feasibility')->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(fn () => Lookup::options('visit_status')),
                SelectFilter::make('assigned_surveyor_id')->label('Surveyor')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
