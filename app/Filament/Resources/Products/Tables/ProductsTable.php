<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Lookup;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')->disk('public')->circular()->toggleable(),
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('sku')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('brand')->searchable()->sortable(),
                TextColumn::make('sale_price')->money('INR')->sortable(),
                TextColumn::make('available_stock')
                    ->label('Available')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->color(fn ($record) => ($record->available_stock ?? 0) <= (float) $record->reorder_threshold ? 'danger' : null),
                TextColumn::make('reorder_threshold')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_archived')->boolean()->label('Archived')->toggleable(),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('title')
            ->filters([
                SelectFilter::make('category')->options(fn () => Lookup::options('product_category')),
                SelectFilter::make('brand')->options(fn () => Lookup::options('product_brand')),
                TernaryFilter::make('is_archived')->label('Archived'),
                TernaryFilter::make('low_stock')
                    ->label('Low stock')
                    ->queries(
                        true: fn ($q) => $q->whereRaw(
                            '(select coalesce(sum(available),0) from stock_balances where stock_balances.product_id = products.id) <= products.reorder_threshold'
                        ),
                        false: fn ($q) => $q->whereRaw(
                            '(select coalesce(sum(available),0) from stock_balances where stock_balances.product_id = products.id) > products.reorder_threshold'
                        ),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('archive')
                    ->label('Archive')
                    ->color('warning')
                    ->visible(fn ($record) => ! $record->is_archived)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->archive()),
            ]);
    }
}
