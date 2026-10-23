<?php

namespace App\Filament\Pages;

use App\Models\Product;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class LowStockProducts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Low stock';

    protected static ?string $title = 'Low-stock alerts';

    protected string $view = 'filament.pages.low-stock-products';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermission('products.view') || $user?->hasPermission('inventory.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->active()
                    ->withSum('balances as available_stock', 'available')
                    ->whereRaw(
                        '(select coalesce(sum(available),0) from stock_balances where stock_balances.product_id = products.id) <= products.reorder_threshold'
                    )
            )
            ->columns([
                TextColumn::make('sku')->searchable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('category')->badge(),
                TextColumn::make('available_stock')->label('Available')->numeric(decimalPlaces: 1),
                TextColumn::make('reorder_threshold')->label('Reorder at'),
                TextColumn::make('brand'),
            ])
            ->defaultSort('title');
    }
}
