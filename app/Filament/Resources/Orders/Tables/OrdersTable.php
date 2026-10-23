<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('quotation.number')->label('Quote')->searchable(),
                TextColumn::make('customer.name')->searchable()->toggleable(),
                TextColumn::make('project.number')->label('Project')->toggleable(),
                TextColumn::make('contract_value')->money('INR'),
                TextColumn::make('status')->badge(),
                TextColumn::make('ordered_on')->date()->sortable(),
                TextColumn::make('delivery_target')->date()->toggleable(),
            ])
            ->defaultSort('ordered_on', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query, $livewire) {
                // tabs handled on ListOrders
                return $query;
            })
            ->recordActions([
                EditAction::make()->label('Open'),
            ]);
    }
}
