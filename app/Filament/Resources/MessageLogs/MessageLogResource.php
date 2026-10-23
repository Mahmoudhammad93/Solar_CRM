<?php

namespace App\Filament\Resources\MessageLogs;

use App\Filament\Resources\MessageLogs\Pages\ListMessageLogs;
use App\Models\MessageLog;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 96;

    protected static ?string $navigationLabel = 'Message logs';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && ($user->hasPermission('settings.view') || $user->hasPermission('audit.view'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('template_key')->label('Template'),
                TextColumn::make('recipient'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === MessageLog::STATUS_DEMO_SIMULATED ? 'warning' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === MessageLog::STATUS_DEMO_SIMULATED
                        ? 'SIMULATED – not delivered'
                        : $state),
                TextColumn::make('subject_reference')->label('Linked'),
                TextColumn::make('initiator.name')->label('User'),
                TextColumn::make('error_message')->limit(40)->toggleable(),
            ])
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageLogs::route('/'),
        ];
    }
}
