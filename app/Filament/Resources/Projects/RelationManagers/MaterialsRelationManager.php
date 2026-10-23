<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectMaterial;
use App\Models\Warehouse;
use App\Services\AllocationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Materials (BOM)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku'),
                TextColumn::make('title')->wrap(),
                TextColumn::make('quantity_required')->label('Required'),
                TextColumn::make('available')->label('Available')
                    ->getStateUsing(fn (ProjectMaterial $r) => $r->is_service ? 'n/a' : $r->availableStock()),
                TextColumn::make('quantity_reserved')->label('Reserved'),
                TextColumn::make('quantity_allocated')->label('Allocated'),
                TextColumn::make('quantity_issued')->label('Issued'),
                IconColumn::make('shortage')->boolean()->label('Shortage')
                    ->getStateUsing(fn (ProjectMaterial $r) => $r->hasShortage()),
                TextColumn::make('warehouse.code')->label('WH'),
            ])
            ->recordActions([
                Action::make('setWarehouse')
                    ->form([
                        Select::make('warehouse_id')
                            ->options(fn () => Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (ProjectMaterial $record, array $data): void {
                        $record->update(['warehouse_id' => $data['warehouse_id']]);
                    })
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
                Action::make('reserve')
                    ->form([TextInput::make('quantity')->numeric()->required()->minValue(0.001)])
                    ->action(fn (ProjectMaterial $r, array $d) => $this->runStock($r, 'reserve', (float) $d['quantity']))
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
                Action::make('allocate')
                    ->form([TextInput::make('quantity')->numeric()->required()->minValue(0.001)])
                    ->action(fn (ProjectMaterial $r, array $d) => $this->runStock($r, 'allocate', (float) $d['quantity']))
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
                Action::make('issue')
                    ->form([TextInput::make('quantity')->numeric()->required()->minValue(0.001)])
                    ->action(fn (ProjectMaterial $r, array $d) => $this->runStock($r, 'issue', (float) $d['quantity']))
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
                Action::make('return')
                    ->form([TextInput::make('quantity')->numeric()->required()->minValue(0.001)])
                    ->action(fn (ProjectMaterial $r, array $d) => $this->runStock($r, 'returnStock', (float) $d['quantity']))
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
                Action::make('damage')
                    ->color('danger')
                    ->form([TextInput::make('quantity')->numeric()->required()->minValue(0.001)])
                    ->action(fn (ProjectMaterial $r, array $d) => $this->runStock($r, 'damage', (float) $d['quantity']))
                    ->visible(fn (ProjectMaterial $r) => ! $r->is_service),
            ]);
    }

    private function runStock(ProjectMaterial $material, string $method, float $qty): void
    {
        try {
            app(AllocationService::class)->{$method}($material, $qty, auth()->user());
            Notification::make()->title(ucfirst($method).' OK')->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title('Stock action failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
        }
    }
}
