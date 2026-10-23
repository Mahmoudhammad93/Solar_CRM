<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Lookup;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Lead ID')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->toggleable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('mobile')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('city')->sortable()->toggleable(),
                TextColumn::make('source')->badge()->toggleable(),
                TextColumn::make('stage')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Owner')->sortable()->toggleable(),
                TextColumn::make('priority')->toggleable(),
                TextColumn::make('next_follow_up_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('expected_value')->money('EGP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('stage')->options(fn () => Lookup::options('lead_stage')),
                SelectFilter::make('source')->options(fn () => Lookup::options('lead_source')),
                SelectFilter::make('priority')->options(fn () => Lookup::options('priority')),
                SelectFilter::make('assigned_to')->label('Owner')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('system_type')->options(fn () => Lookup::options('system_type')),
                Filter::make('follow_up_due')
                    ->label('Follow-up due/overdue')
                    ->query(fn (Builder $query) => $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now())),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
