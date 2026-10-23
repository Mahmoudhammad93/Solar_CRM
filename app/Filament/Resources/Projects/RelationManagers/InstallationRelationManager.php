<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;

class InstallationRelationManager extends RelationManager
{
    protected static string $relationship = 'installation';

    protected static ?string $title = 'Installation';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('crew'),
            DateTimePicker::make('scheduled_at'),
            TextInput::make('status'),
            Repeater::make('checklist')->schema([
                TextInput::make('item')->required(),
                Toggle::make('done'),
            ])->columnSpanFull(),
            KeyValue::make('serial_numbers')->keyLabel('Component')->valueLabel('Serial'),
            KeyValue::make('commissioning_readings')->keyLabel('Reading')->valueLabel('Value'),
            Textarea::make('safety_notes')->columnSpanFull(),
            FileUpload::make('customer_signoff_path')
                ->label('Customer sign-off (Demo e-signature, not legally verified)')
                ->image()
                ->disk('public')
                ->directory('signoffs'),
            Textarea::make('handover_notes')->columnSpanFull(),
            TextInput::make('completion_report_path')->disabled()->helperText('Completion PDF: Partial in Phase 7 — checklist/readings/photos/sign-off shipped.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('crew'),
                TextColumn::make('scheduled_at')->dateTime(),
                TextColumn::make('status')->badge(),
                TextColumn::make('signed_off_at')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
