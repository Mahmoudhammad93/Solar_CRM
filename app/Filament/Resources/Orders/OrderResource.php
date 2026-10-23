<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Support\RecordScoping;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'My Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return RecordScoping::applyAssignedScope(
            parent::getEloquentQuery()->with(['quotation', 'project', 'customer']),
            Filament::auth()->user(),
            'owner_id',
        );
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('orders.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false; // only via quotation conversion
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('orders.edit')
            && RecordScoping::canAccessOwnedRecord($user, $record->owner_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
