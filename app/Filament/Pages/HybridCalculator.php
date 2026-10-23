<?php

namespace App\Filament\Pages;

use App\Models\CalculationRun;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Visit;
use App\Services\CalculationReportService;
use App\Services\Calculators\HybridCalculatorService;
use App\Services\SettingsService;
use App\Support\CalculatorAssumptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class HybridCalculator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Calculators';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Hybrid solar';

    protected static ?string $title = 'Hybrid solar calculator';

    protected string $view = 'filament.pages.hybrid-calculator';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?int $lastRunId = null;

    /** @var array<string, mixed>|null */
    public ?array $lastOutputs = null;

    /** @var array<string, mixed>|null */
    public ?array $lastSteps = null;

    public ?string $lastReference = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user?->hasPermission('calculators.view') || $user?->hasPermission('calculators.create') ?? false;
    }

    public function mount(): void
    {
        $s = app(SettingsService::class);
        $this->form->fill([
            'backup_hours' => 4,
            'peak_sun_hours' => $s->get('calc.hybrid.peak_sun_hours', 5),
            'performance_ratio' => $s->get('calc.hybrid.performance_ratio', 0.75),
            'panel_wattage' => $s->get('calc.hybrid.panel_wattage', 550),
            'usable_dod' => $s->get('calc.hybrid.usable_dod', 0.9),
            'battery_efficiency' => $s->get('calc.hybrid.battery_efficiency', 0.95),
            'inverter_efficiency' => $s->get('calc.hybrid.inverter_efficiency', 0.95),
            'design_margin' => $s->get('calc.hybrid.design_margin', 1.25),
            'system_voltage' => $s->get('calc.hybrid.system_voltage', 48),
            'self_consumption_share' => $s->get('calc.hybrid.self_consumption_share', 0.7),
            'export_enabled' => (bool) $s->get('calc.hybrid.export_enabled', true),
            'tariff' => $s->get('calc.hybrid.tariff', 8),
            'export_rate' => $s->get('calc.hybrid.export_rate', 3),
            'gst_rate' => $s->get('calc.hybrid.gst_rate', 0.18),
            'constrain_to_roof' => false,
            'loads' => [
                ['name' => 'LED lights', 'qty' => 10, 'rated_w' => 10, 'hours_day' => 5, 'is_critical' => false],
                ['name' => 'Ceiling fan', 'qty' => 2, 'rated_w' => 60, 'hours_day' => 8, 'is_critical' => false],
                ['name' => 'Fridge', 'qty' => 1, 'rated_w' => 150, 'hours_day' => 24, 'is_critical' => true],
            ],
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Links')->columns(3)->schema([
                Select::make('lead_id')->label('Lead')->options(fn () => Lead::query()->orderByDesc('id')->limit(100)->pluck('reference', 'id'))->searchable(),
                Select::make('customer_id')->label('Customer')->options(fn () => Customer::query()->orderByDesc('id')->limit(100)->pluck('customer_number', 'id'))->searchable(),
                Select::make('visit_id')->label('Visit')->options(fn () => Visit::query()->orderByDesc('id')->limit(100)->pluck('reference', 'id'))->searchable(),
            ]),
            Section::make('Load table')->schema([
                Repeater::make('loads')->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('qty')->numeric()->required()->minValue(0)->helperText('≥ 0'),
                    TextInput::make('rated_w')->label('Rated W')->numeric()->required()->minValue(0),
                    TextInput::make('hours_day')->label('Hours/day')->numeric()->required()->minValue(0),
                    TextInput::make('usage_factor')->numeric()->minValue(0)->maxValue(2)->helperText('Default from Settings if blank'),
                    TextInput::make('simultaneity')->numeric()->minValue(0)->maxValue(1),
                    TextInput::make('surge_factor')->numeric()->minValue(0)->maxValue(10),
                    Toggle::make('is_critical')->label('Critical'),
                ])->columns(4)->defaultItems(1)->minItems(1)->addActionLabel('Add load'),
            ]),
            Section::make('Solar / battery / financial')->columns(3)->schema([
                TextInput::make('backup_hours')->numeric()->required()->minValue(0),
                TextInput::make('peak_sun_hours')->numeric()->required()->minValue(0.01)->helperText('h/day'),
                TextInput::make('performance_ratio')->numeric()->required()->minValue(0.0001)->maxValue(1),
                TextInput::make('panel_wattage')->numeric()->required()->minValue(1)->helperText('W'),
                TextInput::make('usable_dod')->label('Usable DoD')->numeric()->required()->minValue(0.0001)->maxValue(1),
                TextInput::make('battery_efficiency')->numeric()->required()->minValue(0.0001)->maxValue(1),
                TextInput::make('inverter_efficiency')->numeric()->required()->minValue(0.0001)->maxValue(1),
                TextInput::make('design_margin')->numeric()->required()->minValue(1),
                TextInput::make('system_voltage')->numeric()->required()->minValue(1)->helperText('V'),
                TextInput::make('self_consumption_share')->numeric()->minValue(0)->maxValue(1),
                Toggle::make('export_enabled'),
                TextInput::make('tariff')->numeric()->minValue(0)->helperText('Sample/Demo unless sourced'),
                TextInput::make('export_rate')->numeric()->minValue(0)->helperText('Sample/Demo'),
                TextInput::make('gst_rate')->numeric()->minValue(0)->maxValue(1),
                TextInput::make('installation')->numeric()->minValue(0),
                TextInput::make('transport')->numeric()->minValue(0),
                Toggle::make('constrain_to_roof'),
                TextInput::make('usable_roof_area_m2')->numeric()->minValue(0)->label('Usable roof area m²'),
                TextInput::make('generator_support_kw')->numeric()->minValue(0),
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
                        Action::make('calculate')
                            ->label('Calculate & save snapshot')
                            ->submit('calculate')
                            ->visible(fn (): bool => Filament::auth()->user()?->hasPermission('calculators.create') ?? false),
                        Action::make('downloadPdf')
                            ->label('Download PDF')
                            ->visible(fn (): bool => $this->lastRunId !== null)
                            ->action(fn () => $this->downloadPdf()),
                        Action::make('rerun')
                            ->label('Re-run with current settings')
                            ->visible(fn (): bool => $this->lastRunId !== null)
                            ->action(fn () => $this->rerun()),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function calculate(): void
    {
        abort_unless(Filament::auth()->user()?->hasPermission('calculators.create'), 403);
        $data = $this->form->getState();
        $links = [
            'lead_id' => $data['lead_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
        ];
        unset($data['lead_id'], $data['customer_id'], $data['visit_id']);

        try {
            $run = app(HybridCalculatorService::class)->runAndStore($data, Filament::auth()->user(), $links);
        } catch (ValidationException $e) {
            Notification::make()->title('Validation failed')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
            throw $e;
        }

        $this->applyRun($run);
        Notification::make()->title('Snapshot saved '.$run->reference)->success()->send();
    }

    public function rerun(): void
    {
        $prev = CalculationRun::query()->findOrFail($this->lastRunId);
        $run = app(HybridCalculatorService::class)->rerun($prev, Filament::auth()->user());
        $this->applyRun($run);
        Notification::make()->title('New run '.$run->reference.' (from '.$prev->reference.')')->success()->send();
    }

    public function downloadPdf(): StreamedResponse
    {
        $run = CalculationRun::query()->findOrFail($this->lastRunId);
        [$binary, $filename] = app(CalculationReportService::class)->generate($run);

        return response()->streamDownload(fn () => print ($binary), $filename, ['Content-Type' => 'application/pdf']);
    }

    private function applyRun(CalculationRun $run): void
    {
        $this->lastRunId = $run->id;
        $this->lastOutputs = $run->outputs;
        $this->lastSteps = $run->steps;
        $this->lastReference = $run->reference;
    }

    public function getDisclaimerProperty(): string
    {
        return CalculatorAssumptions::DISCLAIMER;
    }
}
