<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('code')->required()->unique(ignoreRecord: true)->maxLength(32),
            Select::make('branch_id')
                ->label('Branch')
                ->options(fn () => Branch::query()->where('is_active', true)->pluck('name', 'id'))
                ->searchable(),
            Textarea::make('address')->rows(2)->columnSpanFull(),
            Toggle::make('is_active')->default(true),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }
}
