<?php

namespace App\Filament\Resources\Quotations\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('proposal_type')->badge(),
                TextColumn::make('currentRevision.revision')->label('Rev'),
                TextColumn::make('currentRevision.status')->label('Status')->badge(),
                TextColumn::make('currentRevision.totals.grand_total')->label('Total')->money(fn ($record) => $record->currentRevision?->totals['currency'] ?? 'INR'),
                TextColumn::make('owner.name')->toggleable(),
                TextColumn::make('currentRevision.valid_until')->date()->toggleable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('proposal_type')->options([
                    'hybrid' => 'Hybrid',
                    'pump' => 'Pump',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
