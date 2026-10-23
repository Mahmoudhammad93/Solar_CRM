<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withSum('balances as available_stock', 'available');
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && ($user->hasPermission('products.view') || $user->hasPermission('inventory.view'));
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && ($user->hasPermission('products.create') || $user->hasPermission('inventory.create'));
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && ($user->hasPermission('products.edit') || $user->hasPermission('inventory.edit'));
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()?->hasPermission('products.delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
