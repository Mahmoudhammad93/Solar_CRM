<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Branch')->toggleable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('address')->limit(40)->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
