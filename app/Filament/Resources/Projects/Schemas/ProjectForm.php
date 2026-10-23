<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')->columns(3)->schema([
                TextInput::make('number')->disabled(),
                TextInput::make('status')->disabled(),
                TextInput::make('percent_complete')->disabled()->suffix('%'),
                TextInput::make('current_milestone_key')->disabled()->label('Current milestone'),
                Select::make('manager_id')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                Select::make('sales_user_id')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                Select::make('engineering_user_id')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable(),
                Select::make('priority')->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ]),
                DatePicker::make('planned_start'),
                DatePicker::make('planned_end'),
                DatePicker::make('actual_start'),
                DatePicker::make('actual_end'),
                Textarea::make('risks_blockers')->columnSpanFull(),
            ]),
        ]);
    }
}
