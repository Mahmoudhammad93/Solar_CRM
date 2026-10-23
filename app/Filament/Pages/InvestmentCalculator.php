<?php

namespace App\Filament\Pages;

use App\Services\Calculators\InvestmentCalculatorService;
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
class InvestmentCalculator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Calculators';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Investment';

    protected static ?string $title = 'Solar investment calculator';

    protected string $view = 'filament.pages.investment-calculator';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?string $lastReference = null;

    /** @var array<string, mixed>|null */
    public ?array $lastOutputs = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermission('calculators.view') || $user?->hasPermission('calculators.create') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'upfront_cost' => 250000,
            'subsidy' => 0,
            'loan_principal' => 0,
            'annual_interest_rate' => 0.1,
            'tenure_months' => 60,
            'annual_generation_kwh' => 6000,
            'self_consumption_share' => 0.7,
            'utility_tariff' => 8,
            'export_price' => 3,
            'degradation_per_year' => 0.005,
            'tariff_escalation_per_year' => 0.03,
            'annual_om' => 500,
            'other_annual_cost' => 0,
            'analysis_years' => 10,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Investment inputs')->columns(3)->schema([
                TextInput::make('upfront_cost')->numeric()->required()->minValue(0),
                TextInput::make('subsidy')->numeric()->minValue(0)->helperText('Sample/Demo unless sourced'),
                TextInput::make('loan_principal')->numeric()->minValue(0),
                TextInput::make('annual_interest_rate')->numeric()->minValue(0)->maxValue(1)->helperText('Fraction e.g. 0.1 = 10%'),
                TextInput::make('tenure_months')->numeric()->integer()->minValue(0),
                TextInput::make('annual_generation_kwh')->numeric()->required()->minValue(0),
                TextInput::make('self_consumption_share')->numeric()->minValue(0)->maxValue(1),
                TextInput::make('utility_tariff')->numeric()->minValue(0)->helperText('Sample/Demo'),
                TextInput::make('export_price')->numeric()->minValue(0)->helperText('Sample/Demo'),
                TextInput::make('degradation_per_year')->numeric()->minValue(0)->maxValue(0.2),
                TextInput::make('tariff_escalation_per_year')->numeric()->minValue(0)->maxValue(0.5),
                TextInput::make('annual_om')->numeric()->minValue(0),
                TextInput::make('other_annual_cost')->numeric()->minValue(0),
                TextInput::make('analysis_years')->numeric()->integer()->minValue(1)->maxValue(40),
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
            $run = app(InvestmentCalculatorService::class)->runAndStore($this->form->getState(), Filament::auth()->user());
        } catch (ValidationException $e) {
            Notification::make()->title('Validation failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            throw $e;
        }
        $this->lastReference = $run->reference;
        $this->lastOutputs = $run->outputs;
        Notification::make()->title('Saved '.$run->reference)->success()->send();
    }

    public function getDisclaimerProperty(): string
    {
        return CalculatorAssumptions::DISCLAIMER;
    }
}
