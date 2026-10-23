<?php

namespace App\Filament\Resources\AmcContracts;

use App\Filament\Resources\AmcContracts\Pages\CreateAmcContract;
use App\Filament\Resources\AmcContracts\Pages\EditAmcContract;
use App\Filament\Resources\AmcContracts\Pages\ListAmcContracts;
use App\Models\AmcContract;
use App\Models\Customer;
use App\Models\Project;
use App\Support\AmcStatuses;
use App\Support\RecordScoping;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AmcContractResource extends Resource
{
    protected static ?string $model = AmcContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Service';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'AMC';

    protected static ?string $modelLabel = 'AMC contract';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('AMC')->columns(2)->schema([
                TextInput::make('number')->disabled()->dehydrated(false),
                Select::make('status')->options(AmcStatuses::labels()),
                Select::make('customer_id')->options(fn () => Customer::query()->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
                Select::make('project_id')->options(fn () => Project::query()->orderByDesc('id')->pluck('number', 'id'))->searchable(),
                TextInput::make('site_address')->columnSpanFull(),
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->required(),
                Textarea::make('coverage')->columnSpanFull(),
                TextInput::make('visit_frequency'),
                TextInput::make('sla_hours')->numeric()->integer(),
                TextInput::make('amount')->numeric(),
                DatePicker::make('next_service_on'),
                DatePicker::make('renewal_reminder_on')->helperText('Shown on renewal reminder list / amc:renewal-reminders'),
                Textarea::make('notes')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('project.number')->label('Project')->toggleable(),
                TextColumn::make('starts_on')->date(),
                TextColumn::make('ends_on')->date()->sortable(),
                TextColumn::make('next_service_on')->date()->sortable(),
                TextColumn::make('renewal_reminder_on')->date()->label('Renewal reminder'),
                TextColumn::make('amount')->money('INR'),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('ends_on')
            ->filters([
                SelectFilter::make('status')->options(AmcStatuses::labels()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return RecordScoping::applyAssignedScope(
            parent::getEloquentQuery()->with(['customer', 'project']),
            Filament::auth()->user(),
            'owner_id',
        );
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasPermission('amc.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->hasPermission('amc.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && $user->hasPermission('amc.edit')
            && RecordScoping::canAccessOwnedRecord($user, $record->owner_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAmcContracts::route('/'),
            'create' => CreateAmcContract::route('/create'),
            'edit' => EditAmcContract::route('/{record}/edit'),
        ];
    }
}
