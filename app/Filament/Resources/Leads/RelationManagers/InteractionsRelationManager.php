<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    protected static ?string $title = 'Interaction history';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options([
                    'call' => 'Call',
                    'meeting' => 'Meeting',
                    'note' => 'Note',
                    'follow_up' => 'Follow-up',
                    'email' => 'Email',
                ])
                ->required(),
            TextInput::make('subject')->maxLength(255),
            Textarea::make('body')->label('Call / meeting notes')->rows(4)->required(),
            TextInput::make('outcome')->maxLength(255),
            DateTimePicker::make('interacted_at')->default(now())->seconds(false)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('interacted_at')->dateTime()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('subject')->limit(40),
                TextColumn::make('outcome')->toggleable(),
                TextColumn::make('creator.name')->label('By'),
            ])
            ->defaultSort('interacted_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['interacted_at'] = $data['interacted_at'] ?? now();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
