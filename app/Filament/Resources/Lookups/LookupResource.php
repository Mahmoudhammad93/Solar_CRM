<?php

namespace App\Filament\Resources\Lookups;

use App\Filament\Resources\Lookups\Pages\CreateLookup;
use App\Filament\Resources\Lookups\Pages\EditLookup;
use App\Filament\Resources\Lookups\Pages\ListLookups;
use App\Filament\Resources\Lookups\Schemas\LookupForm;
use App\Filament\Resources\Lookups\Tables\LookupsTable;
use App\Models\Lookup;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LookupResource extends Resource
{
    protected static ?string $model = Lookup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Lookups / dropdowns';

    protected static ?int $navigationSort = 95;

    protected static ?string $modelLabel = 'Lookup value';

    public static function form(Schema $schema): Schema
    {
        return LookupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LookupsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && (
            $user->hasPermission('settings.view')
            || $user->hasPermission('settings.edit')
            || $user->hasPermission('settings.settings')
        );
    }

    public static function canCreate(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null && (
            $user->hasPermission('settings.edit')
            || $user->hasPermission('settings.settings')
        );
    }

    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    public static function canDelete($record): bool
    {
        return static::canCreate();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLookups::route('/'),
            'create' => CreateLookup::route('/create'),
            'edit' => EditLookup::route('/{record}/edit'),
        ];
    }
}
