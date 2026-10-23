<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\Leads\RelationManagers\StageHistoriesRelationManager;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
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

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        return RecordScoping::applyAssignedScope($query, $user, 'assigned_to');
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('leads.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasPermission('leads.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('leads.edit')
            && RecordScoping::canAccessOwnedRecord($user, $record->assigned_to);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('leads.delete')
            && RecordScoping::canAccessOwnedRecord($user, $record->assigned_to);
    }

    public static function canView(Model $record): bool
    {
        return static::canEdit($record) || (
            Filament::auth()->user()?->hasPermission('leads.view')
            && RecordScoping::canAccessOwnedRecord(Filament::auth()->user(), $record->assigned_to)
        );
    }

    public static function getRelations(): array
    {
        return [
            InteractionsRelationManager::class,
            StageHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
