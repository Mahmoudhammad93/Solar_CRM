<?php

namespace App\Filament\Pages;

use App\Services\Calculators\EvCalculatorService;
use App\Services\SettingsService;
use App\Support\CalculatorAssumptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** @property-read Schema $form */
class EvCalculator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Calculators';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'EV charging';

    protected static ?string $title = 'EV charging income calculator';

    protected string $view = 'filament.pages.ev-calculator';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $lastReference = null;

    /** @var array<string, mixed>|null */
    public ?array $lastOutputs = null;

    /** @var array<string, mixed>|null */
    public ?array $lastSteps = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermission('calculators.view') || $user?->hasPermission('calculators.create') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'charger_power_kw' => 7,
            'points' => 4,
            'sessions_per_day' => 3,
            'kwh_per_session' => 20,
            'operating_days_month' => 26,
            'selling_price' => 18,
            'purchase_cost' => 8,
            'utilization' => app(SettingsService::class)->get('calc.ev.default_utilization', 0.6),
            'rent_month' => 5000,
            'staff_month' => 8000,
            'maintenance_month' => 2000,
            'other_month' => 500,
            'capital_cost' => 400000,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('EV inputs')->columns(3)->schema([
                TextInput::make('charger_power_kw')->numeric()->required()->minValue(0.01)->helperText('Informational; energy from sessions×kWh'),
                TextInput::make('points')->numeric()->required()->minValue(0.01),
                TextInput::make('sessions_per_day')->numeric()->required()->minValue(0),
                TextInput::make('kwh_per_session')->numeric()->required()->minValue(0),
                TextInput::make('operating_days_month')->numeric()->required()->minValue(0.01),
                TextInput::make('selling_price')->numeric()->required()->minValue(0),
                TextInput::make('purchase_cost')->numeric()->required()->minValue(0),
                TextInput::make('utilization')->numeric()->required()->minValue(0)->maxValue(1)->helperText('Scales sessions only (no double count)'),
                TextInput::make('rent_month')->numeric()->minValue(0),
                TextInput::make('staff_month')->numeric()->minValue(0),
                TextInput::make('maintenance_month')->numeric()->minValue(0),
                TextInput::make('other_month')->numeric()->minValue(0),
                TextInput::make('capital_cost')->numeric()->minValue(0),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('calculate')
                ->footer([
                    Actions::make([
                        Action::make('calculate')->label('Calculate & save')->submit('calculate'),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function calculate(): void
    {
        abort_unless(Filament::auth()->user()?->hasPermission('calculators.create'), 403);
        try {
            $run = app(EvCalculatorService::class)->runAndStore($this->form->getState(), Filament::auth()->user());
        } catch (ValidationException $e) {
            Notification::make()->title('Validation failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            throw $e;
        }
        $this->lastReference = $run->reference;
        $this->lastOutputs = $run->outputs;
        $this->lastSteps = $run->steps;
        Notification::make()->title('Saved '.$run->reference)->success()->send();
    }

    public function getDisclaimerProperty(): string
    {
        return CalculatorAssumptions::DISCLAIMER;
    }
}
