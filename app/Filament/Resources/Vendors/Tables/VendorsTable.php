<?php

namespace App\Filament\Resources\Vendors\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('contact_name')->toggleable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('email')->toggleable(),
                TextColumn::make('tax_identifier')->label('Tax ID')->toggleable(),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
