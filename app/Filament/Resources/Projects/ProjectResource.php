<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\RelationManagers\InstallationRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MaterialsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'quotation', 'customer', 'milestones']);
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('projects.view')
            || Filament::auth()->user()?->hasPermission('orders.view')
            ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()?->hasPermission('projects.edit')
            || Filament::auth()->user()?->isSuperAdmin()
            ?? false;
    }

    public static function getRelations(): array
    {
        return [
            MilestonesRelationManager::class,
            MaterialsRelationManager::class,
            InstallationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
