<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StageHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'stageHistories';

    protected static ?string $title = 'Stage history';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('from_stage')->label('From'),
                TextColumn::make('to_stage')->label('To')->badge(),
                TextColumn::make('changer.name')->label('By'),
                TextColumn::make('notes')->limit(50),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
