<?php

namespace App\Filament\Resources\Visits;

use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Filament\Resources\Visits\Pages\EditVisit;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Resources\Visits\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Visits\RelationManagers\HistoriesRelationManager;
use App\Filament\Resources\Visits\Schemas\VisitForm;
use App\Filament\Resources\Visits\Tables\VisitsTable;
use App\Models\Visit;
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

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Field';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return VisitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VisitsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return RecordScoping::applyAssignedScope(
            parent::getEloquentQuery(),
            Filament::auth()->user(),
            'assigned_surveyor_id',
        );
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('visits.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasPermission('visits.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('visits.edit')
            && RecordScoping::canAccessOwnedRecord($user, $record->assigned_surveyor_id);
    }

    public static function canView(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('visits.view')
            && RecordScoping::canAccessOwnedRecord($user, $record->assigned_surveyor_id);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisits::route('/'),
            'create' => CreateVisit::route('/create'),
            'edit' => EditVisit::route('/{record}/edit'),
        ];
    }
}
