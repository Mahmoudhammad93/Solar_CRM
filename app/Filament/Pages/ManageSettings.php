<?php

namespace App\Filament\Pages;

use App\Services\BrandingService;
use App\Services\NumberingService;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('settings.view');
    }

    public function mount(): void
    {
        abort_unless(Gate::allows('settings.view'), 403);

        $settings = app(SettingsService::class);

        $this->form->fill([
            'app_name' => $settings->get('branding.app_name', config('app.name')),
            'company_name' => $settings->get('branding.company_name'),
            'legal_name' => $settings->get('branding.legal_name'),
            'address' => $settings->get('branding.address'),
            'phone' => $settings->get('branding.phone'),
            'support_email' => $settings->get('branding.support_email'),
            'website' => $settings->get('branding.website'),
            'gstin' => $settings->get('branding.gstin'),
            'invoice_address' => $settings->get('branding.invoice_address'),
            'footer_text' => $settings->get('branding.footer_text'),
            'accent_color' => $settings->get('branding.accent_color', '#F59E0B'),
            'logo_path' => $settings->get('branding.logo_path'),
            'favicon_path' => $settings->get('branding.favicon_path'),
            'login_logo_path' => $settings->get('branding.login_logo_path'),
            'session_idle_minutes' => $settings->get('security.session_idle_minutes', 120),
            'password_min_length' => $settings->get('security.password_min_length', 8),
            'lead_prefix' => $settings->get('numbering.lead.prefix', 'LEAD'),
            'lead_format' => $settings->get('numbering.lead.format', '{PREFIX}-{YYYY}-{####}'),
            'lead_reset_yearly' => (bool) $settings->get('numbering.lead.reset_yearly', false),
            'quote_prefix' => $settings->get('numbering.quote.prefix', 'QUO'),
            'quote_format' => $settings->get('numbering.quote.format', '{PREFIX}-{YYYY}-{####}'),
            'quote_reset_yearly' => (bool) $settings->get('numbering.quote.reset_yearly', false),
            'default_reorder_threshold' => $settings->get('inventory.default_reorder_threshold', 5),
            'serial_batch_tracking_default' => (bool) $settings->get('inventory.serial_batch_tracking_default', false),
            'reservation_behavior' => $settings->get('inventory.reservation_behavior', 'hard'),
            'allow_negative_stock' => (bool) $settings->get('inventory.allow_negative_stock', false),
            'default_gst_rate' => $settings->get('inventory.default_gst_rate', 18),
            'default_warranty_months' => $settings->get('inventory.default_warranty_months', 12),
            'calc_psh' => $settings->get('calc.hybrid.peak_sun_hours', 5),
            'calc_pr' => $settings->get('calc.hybrid.performance_ratio', 0.75),
            'calc_loss' => $settings->get('calc.hybrid.system_loss_factor', 0),
            'calc_panel_w' => $settings->get('calc.hybrid.panel_wattage', 550),
            'calc_dod' => $settings->get('calc.hybrid.usable_dod', 0.9),
            'calc_batt_eff' => $settings->get('calc.hybrid.battery_efficiency', 0.95),
            'calc_inv_eff' => $settings->get('calc.hybrid.inverter_efficiency', 0.95),
            'calc_margin' => $settings->get('calc.hybrid.design_margin', 1.25),
            'calc_tariff' => $settings->get('calc.hybrid.tariff', 8),
            'calc_export' => $settings->get('calc.hybrid.export_rate', 3),
            'project_delivery_days' => $settings->get('projects.default_delivery_days', 45),
            'project_duration_days' => $settings->get('projects.default_duration_days', 60),
            'project_milestone_sla_days' => $settings->get('projects.milestone_sla_days', 7),
            'ticket_sla_hours' => $settings->get('tickets.default_sla_hours', 48),
            'amc_sla_hours' => $settings->get('amc.default_sla_hours', 72),
            'amc_renewal_days' => $settings->get('amc.renewal_reminder_days', 30),
            'locale' => $settings->get('regional.locale', 'en_IN'),
            'timezone' => $settings->get('regional.timezone', 'Asia/Kolkata'),
            'date_format' => $settings->get('regional.date_format', 'Y-m-d'),
            'time_format' => $settings->get('regional.time_format', 'H:i'),
            'currency_symbol' => $settings->get('regional.currency_symbol', '₹'),
            'currency_precision' => $settings->get('regional.currency_precision', 2),
            'measurement_units' => $settings->get('regional.measurement_units', 'metric'),
            'rounding_behavior' => $settings->get('regional.rounding', 'none'),
            'default_calling_code' => $settings->get('branding.default_calling_code', '+91'),
            'fiscal_year_start_month' => $settings->get('finance.fiscal_year_start_month', 4),
            'company_state' => $settings->get('finance.company_state', 'Maharashtra'),
            'tax_split_rule' => $settings->get('finance.tax_split_rule', 'auto'),
            'cgst_rate' => $settings->get('finance.cgst_rate', 0.09),
            'sgst_rate' => $settings->get('finance.sgst_rate', 0.09),
            'igst_rate' => $settings->get('finance.igst_rate', 0.18),
            'finance_round_off' => $settings->get('finance.round_off', 'none'),
            'quote_validity_days' => $settings->get('quotations.validity_days', 15),
            'quote_terms' => $settings->get('quotations.default_terms'),
            'qr_enabled' => (bool) $settings->get('quotations.qr_enabled', true),
            'signature_block_enabled' => (bool) $settings->get('documents.signature_block_enabled', true),
            'footer_block_enabled' => (bool) $settings->get('documents.footer_block_enabled', true),
            'logo_on_pdf' => (bool) $settings->get('documents.logo_on_pdf', true),
            'api_enabled' => (bool) $settings->get('api.enabled', false),
            'export_csv_enabled' => (bool) $settings->get('exports.csv_enabled', true),
            'export_pdf_enabled' => (bool) $settings->get('exports.pdf_enabled', true),
            'backup_reminder_days' => $settings->get('system.backup_reminder_days', 7),
            'retention_days' => $settings->get('system.audit_retention_days', 365),
            'assignment_rule' => $settings->get('assignment.default_rule', 'manual'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $canEdit = Gate::allows('settings.update');

        return $schema->components([
            Tabs::make('SettingsTabs')->tabs([
                Tab::make('Branding')->schema([
                    Section::make('Application branding')
                        ->description('Changes appear in navigation, login, footer, and later PDFs. This updates display branding, not DNS.')
                        ->schema([
                            TextInput::make('app_name')->label('Application / website display name')->required()->disabled(! $canEdit),
                            TextInput::make('company_name')->label('Organization / company name')->disabled(! $canEdit),
                            TextInput::make('legal_name')->label('Legal name')->disabled(! $canEdit),
                            Textarea::make('address')->rows(2)->disabled(! $canEdit),
                            TextInput::make('phone')->tel()->disabled(! $canEdit),
                            TextInput::make('support_email')->email()->disabled(! $canEdit),
                            TextInput::make('website')->url()->disabled(! $canEdit),
                            TextInput::make('gstin')->label('GSTIN / tax ID')->disabled(! $canEdit),
                            Textarea::make('invoice_address')->rows(2)->disabled(! $canEdit),
                            TextInput::make('footer_text')->disabled(! $canEdit),
                            ColorPicker::make('accent_color')->disabled(! $canEdit),
                            FileUpload::make('logo_path')
                                ->label('Logo')
                                ->image()
                                ->disk('public')
                                ->directory('branding')
                                ->visibility('public')
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                ->imagePreviewHeight('80')
                                ->disabled(! $canEdit),
                            FileUpload::make('login_logo_path')
                                ->label('Login-page logo')
                                ->image()
                                ->disk('public')
                                ->directory('branding')
                                ->visibility('public')
                                ->maxSize(2048)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                ->disabled(! $canEdit),
                            FileUpload::make('favicon_path')
                                ->label('Favicon')
                                ->image()
                                ->disk('public')
                                ->directory('branding')
                                ->visibility('public')
                                ->maxSize(512)
                                ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/webp'])
                                ->disabled(! $canEdit),
                        ])->columns(2),
                ]),
                Tab::make('Security')->schema([
                    Section::make('Session & password policy')
                        ->schema([
                            TextInput::make('session_idle_minutes')
                                ->numeric()
                                ->minValue(5)
                                ->maxValue(1440)
                                ->helperText('Idle timeout in minutes (applied from Settings).')
                                ->disabled(! $canEdit),
                            TextInput::make('password_min_length')
                                ->numeric()
                                ->minValue(8)
                                ->maxValue(128)
                                ->helperText('Minimum password length for password changes.')
                                ->disabled(! $canEdit),
                            Placeholder::make('password_change_hint')
                                ->label('Change password')
                                ->content('Use the Account → Change password page to update your own password (current + new + confirmation).'),
                        ])->columns(2),
                ]),
                Tab::make('Numbering')->schema([
                    Section::make('Reference numbering')
                        ->description('Prefixes/formats used by NumberingService. Changes apply to newly allocated references.')
                        ->schema([
                            TextInput::make('lead_prefix')->label('Lead prefix')->disabled(! $canEdit),
                            TextInput::make('lead_format')->label('Lead format')->helperText('Tokens: {PREFIX} {YYYY} {####}')->disabled(! $canEdit),
                            Toggle::make('lead_reset_yearly')->label('Reset lead sequence yearly')->disabled(! $canEdit),
                            TextInput::make('quote_prefix')->label('Quote prefix')->disabled(! $canEdit),
                            TextInput::make('quote_format')->label('Quote format')->disabled(! $canEdit),
                            Toggle::make('quote_reset_yearly')->label('Reset quote sequence yearly')->disabled(! $canEdit),
                        ])->columns(2),
                ]),
                Tab::make('Calculators')->schema([
                    Section::make('Hybrid defaults (Settings E / SET-04)')
                        ->description('Changes affect NEW calculation runs only. Saved snapshots stay immutable. Tariff/export are Sample/Demo unless source metadata is set.')
                        ->schema([
                            TextInput::make('calc_psh')->numeric()->label('Peak sun hours')->helperText('h/day')->disabled(! $canEdit),
                            TextInput::make('calc_pr')->numeric()->label('Performance ratio')->disabled(! $canEdit),
                            TextInput::make('calc_loss')->numeric()->label('System loss factor (if separate)')->disabled(! $canEdit),
                            TextInput::make('calc_panel_w')->numeric()->label('Default panel wattage (W)')->disabled(! $canEdit),
                            TextInput::make('calc_dod')->numeric()->label('Usable DoD')->disabled(! $canEdit),
                            TextInput::make('calc_batt_eff')->numeric()->label('Battery efficiency')->disabled(! $canEdit),
                            TextInput::make('calc_inv_eff')->numeric()->label('Inverter efficiency')->disabled(! $canEdit),
                            TextInput::make('calc_margin')->numeric()->label('Design margin (≥1)')->disabled(! $canEdit),
                            TextInput::make('calc_tariff')->numeric()->label('Tariff (Sample/Demo)')->disabled(! $canEdit),
                            TextInput::make('calc_export')->numeric()->label('Export rate (Sample/Demo)')->disabled(! $canEdit),
                        ])->columns(2),
                ]),
                Tab::make('Inventory')->schema([
                    Section::make('Product & stock policy (Settings F)')
                        ->description('Warehouses are managed under Inventory → Warehouses. Categories/units/brands are Lookups. Negative stock may only be enabled by Super Admin and is audit-logged when used.')
                        ->schema([
                            TextInput::make('default_reorder_threshold')->numeric()->label('Default reorder threshold')->disabled(! $canEdit),
                            Toggle::make('serial_batch_tracking_default')->label('Serial/batch tracking default on new products')->disabled(! $canEdit),
                            Select::make('reservation_behavior')
                                ->label('Stock reservation behavior')
                                ->options([
                                    'hard' => 'Hard — block when insufficient available',
                                    'soft' => 'Soft — allow reserve attempt (still blocked if negative policy off)',
                                ])
                                ->disabled(! $canEdit),
                            Toggle::make('allow_negative_stock')
                                ->label('Allow negative stock (protected)')
                                ->helperText('Only Super Admin / inventory.settings may use negative balances when enabled.')
                                ->disabled(! $canEdit || (! Auth::user()?->isSuperAdmin() && ! Auth::user()?->hasPermission('inventory.settings'))),
                            TextInput::make('default_gst_rate')->numeric()->label('Default GST %')->disabled(! $canEdit),
                            TextInput::make('default_warranty_months')->numeric()->integer()->label('Default warranty (months)')->disabled(! $canEdit),
                            Placeholder::make('inventory_lists_hint')
                                ->label('Lists')
                                ->content('Edit product_category, product_unit, and product_brand under Administration → Lookups. Manage warehouses under Inventory → Warehouses.'),
                        ])->columns(2),
                ]),
                Tab::make('Projects')->schema([
                    Section::make('Order / project defaults (Phase 7)')
                        ->description('Milestone catalogue defaults: Order Confirmed → … → Closed. Hold/exception require a recorded reason. SLA days set due dates used for stuck/overdue detection.')
                        ->schema([
                            TextInput::make('project_delivery_days')->numeric()->integer()->label('Default delivery target (days)')->disabled(! $canEdit),
                            TextInput::make('project_duration_days')->numeric()->integer()->label('Default project duration (days)')->disabled(! $canEdit),
                            TextInput::make('project_milestone_sla_days')->numeric()->integer()->label('Milestone SLA step (days)')->disabled(! $canEdit),
                        ])->columns(3),
                ]),
                Tab::make('Service')->schema([
                    Section::make('Tickets & AMC SLA')
                        ->schema([
                            TextInput::make('ticket_sla_hours')->numeric()->integer()->label('Ticket default SLA (hours)')->disabled(! $canEdit),
                            TextInput::make('amc_sla_hours')->numeric()->integer()->label('AMC default SLA (hours)')->disabled(! $canEdit),
                            TextInput::make('amc_renewal_days')->numeric()->integer()->label('AMC renewal reminder (days before end)')->disabled(! $canEdit),
                        ])->columns(3),
                ]),
                Tab::make('Lists & workflows')->schema([
                    Section::make('Dynamic lists (Lookups)')
                        ->description('Add/edit/deactivate values under Administration → Lookups. Deactivated values remain on existing records and appear as “(inactive)” when preserved.')
                        ->schema([
                            Placeholder::make('lists_hint')
                                ->label('Managed types')
                                ->content('lead_source, lead_stage, customer_type, property_type, product_category/unit/brand, priority, loss_reason, task_type/status, document_type, ticket_type/status, quotation_status, amc_status, notification_category, territory.'),
                            Placeholder::make('workflow_hint')
                                ->label('Workflow transitions')
                                ->content('Ticket/quotation/AMC allowed transitions remain code-backed (TicketWorkflow / QuotationWorkflow / AmcWorkflow) for safety. Labels come from Lookups so new statuses appear in forms/filters. Custom fields: Not implemented.'),
                            Placeholder::make('org_hint')
                                ->label('Branches / warehouses / assignment')
                                ->content('Warehouses: Inventory → Warehouses. Branches: Administration → Branches. Assignment rule below (manual default). Round-robin auto-assign: Unavailable.'),
                            Select::make('assignment_rule')->options([
                                'manual' => 'Manual assignment',
                                'by_branch' => 'Prefer same branch (advisory)',
                                'by_team' => 'Prefer same team (advisory)',
                            ])->disabled(! $canEdit),
                        ]),
                ]),
                Tab::make('Regional & finance')->schema([
                    Section::make('Regional')->schema([
                        TextInput::make('locale')->disabled(! $canEdit),
                        TextInput::make('timezone')->disabled(! $canEdit),
                        TextInput::make('date_format')->disabled(! $canEdit),
                        TextInput::make('time_format')->disabled(! $canEdit),
                        TextInput::make('currency_symbol')->disabled(! $canEdit),
                        TextInput::make('currency_precision')->numeric()->integer()->disabled(! $canEdit),
                        Select::make('measurement_units')->options(['metric' => 'Metric', 'imperial' => 'Imperial'])->disabled(! $canEdit),
                        Select::make('rounding_behavior')->options(['none' => 'None', 'nearest' => 'Nearest', 'up' => 'Up', 'down' => 'Down'])->disabled(! $canEdit),
                        TextInput::make('default_calling_code')->label('Default calling code')->disabled(! $canEdit),
                    ])->columns(3),
                    Section::make('Tax & fiscal year')->schema([
                        TextInput::make('fiscal_year_start_month')->numeric()->integer()->minValue(1)->maxValue(12)->label('Fiscal year start month')->disabled(! $canEdit),
                        TextInput::make('company_state')->disabled(! $canEdit),
                        Select::make('tax_split_rule')->options(['auto' => 'Auto CGST/SGST vs IGST', 'force_igst' => 'Force IGST', 'force_cgst_sgst' => 'Force CGST+SGST'])->disabled(! $canEdit),
                        TextInput::make('cgst_rate')->numeric()->label('CGST rate (fraction)')->disabled(! $canEdit),
                        TextInput::make('sgst_rate')->numeric()->label('SGST rate (fraction)')->disabled(! $canEdit),
                        TextInput::make('igst_rate')->numeric()->label('IGST rate (fraction)')->disabled(! $canEdit),
                        Select::make('finance_round_off')->options(['none' => 'None', 'nearest' => 'Nearest'])->disabled(! $canEdit),
                    ])->columns(3),
                ]),
                Tab::make('Documents')->schema([
                    Section::make('Quote / document templates')->schema([
                        TextInput::make('quote_validity_days')->numeric()->integer()->disabled(! $canEdit),
                        Textarea::make('quote_terms')->rows(3)->columnSpanFull()->disabled(! $canEdit),
                        Toggle::make('qr_enabled')->label('QR on quotation PDF')->disabled(! $canEdit),
                        Toggle::make('signature_block_enabled')->label('Signature block')->disabled(! $canEdit),
                        Toggle::make('footer_block_enabled')->label('Footer / contact block')->disabled(! $canEdit),
                        Toggle::make('logo_on_pdf')->label('Logo on PDFs')->disabled(! $canEdit),
                        Placeholder::make('install_pdf')
                            ->label('Installation completion report template')
                            ->content('Partial — basic project fields exist; full commissioning template polish later.'),
                    ])->columns(2),
                ]),
                Tab::make('Integrations')->schema([
                    Section::make('Integration status (secrets server-side only)')->schema([
                        Placeholder::make('status_map')->label('Map / GPS')->content('Demo — preview labelled; live maps Not configured.'),
                        Placeholder::make('status_sig')->label('E-signature')->content('Demo — capture stored; not legally verified.'),
                        Placeholder::make('status_pay')->label('Payment gateway')->content('Simulated / Not configured — manual payments only.'),
                        Placeholder::make('status_backup')->label('Automated backup')->content('Not configured — see docs/backup-plan.md (mysqldump plan).'),
                        Placeholder::make('status_ext')->label('External subsidy / tariff APIs')->content('Not configured — Sample/Demo rates in Settings.'),
                        Placeholder::make('status_wa')->label('WhatsApp / Email')->content('Demo default — Administration → Communications. Credentials: Configured/Not configured from env.'),
                    ])->columns(2),
                ]),
                Tab::make('API & system')->schema([
                    Section::make('REST API')->schema([
                        Toggle::make('api_enabled')->label('Enable REST API')->helperText('When off, /api/v1/* returns 503. Tokens: Administration → API tokens. Full secret shown once at creation only.')->disabled(! $canEdit),
                        Placeholder::make('api_docs')->label('Documentation')->content('See docs/api.md and README. Endpoint: GET /api/v1/leads (Bearer token, permission-scoped).'),
                    ]),
                    Section::make('Exports & backup preferences')->schema([
                        Toggle::make('export_csv_enabled')->label('Allow CSV exports')->disabled(! $canEdit),
                        Toggle::make('export_pdf_enabled')->label('Allow PDF exports')->disabled(! $canEdit),
                        TextInput::make('backup_reminder_days')->numeric()->integer()->label('Backup reminder interval (days)')->disabled(! $canEdit),
                        TextInput::make('retention_days')->numeric()->integer()->label('Audit retention preference (days)')->helperText('Preference only — automated purge Unavailable.')->disabled(! $canEdit),
                        Placeholder::make('restore_warn')
                            ->label('Dangerous restore')
                            ->content('Unavailable — no automated restore UI. Manual restore requires confirmation + authorized ops (see docs/backup-plan.md).'),
                    ])->columns(2),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save')
                            ->submit('save')
                            ->visible(fn (): bool => Gate::allows('settings.update')),
                        Action::make('cancel')
                            ->label('Cancel')
                            ->color('gray')
                            ->action(fn () => $this->mount()),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(Gate::allows('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);
        $userId = Auth::id();

        $map = [
            'branding.app_name' => ['value' => $data['app_name'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.company_name' => ['value' => $data['company_name'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.legal_name' => ['value' => $data['legal_name'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.address' => ['value' => $data['address'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.phone' => ['value' => $data['phone'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.support_email' => ['value' => $data['support_email'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.website' => ['value' => $data['website'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.gstin' => ['value' => $data['gstin'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.invoice_address' => ['value' => $data['invoice_address'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.footer_text' => ['value' => $data['footer_text'] ?? null, 'group' => 'branding', 'type' => 'string'],
            'branding.accent_color' => ['value' => $data['accent_color'] ?? '#F59E0B', 'group' => 'branding', 'type' => 'string'],
            'branding.logo_path' => ['value' => $this->normalizeUpload($data['logo_path'] ?? null), 'group' => 'branding', 'type' => 'file'],
            'branding.favicon_path' => ['value' => $this->normalizeUpload($data['favicon_path'] ?? null), 'group' => 'branding', 'type' => 'file'],
            'branding.login_logo_path' => ['value' => $this->normalizeUpload($data['login_logo_path'] ?? null), 'group' => 'branding', 'type' => 'file'],
            'security.session_idle_minutes' => ['value' => $data['session_idle_minutes'] ?? 120, 'group' => 'security', 'type' => 'integer'],
            'security.password_min_length' => ['value' => $data['password_min_length'] ?? 8, 'group' => 'security', 'type' => 'integer'],
            'numbering.lead.prefix' => ['value' => $data['lead_prefix'] ?? 'LEAD', 'group' => 'numbering', 'type' => 'string'],
            'numbering.lead.format' => ['value' => $data['lead_format'] ?? '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string'],
            'numbering.lead.reset_yearly' => ['value' => (bool) ($data['lead_reset_yearly'] ?? false), 'group' => 'numbering', 'type' => 'boolean'],
            'numbering.quote.prefix' => ['value' => $data['quote_prefix'] ?? 'QUO', 'group' => 'numbering', 'type' => 'string'],
            'numbering.quote.format' => ['value' => $data['quote_format'] ?? '{PREFIX}-{YYYY}-{####}', 'group' => 'numbering', 'type' => 'string'],
            'numbering.quote.reset_yearly' => ['value' => (bool) ($data['quote_reset_yearly'] ?? false), 'group' => 'numbering', 'type' => 'boolean'],
            'inventory.default_reorder_threshold' => ['value' => $data['default_reorder_threshold'] ?? 5, 'group' => 'inventory', 'type' => 'integer'],
            'inventory.serial_batch_tracking_default' => ['value' => (bool) ($data['serial_batch_tracking_default'] ?? false), 'group' => 'inventory', 'type' => 'boolean'],
            'inventory.reservation_behavior' => ['value' => $data['reservation_behavior'] ?? 'hard', 'group' => 'inventory', 'type' => 'string'],
            'inventory.default_gst_rate' => ['value' => $data['default_gst_rate'] ?? 18, 'group' => 'inventory', 'type' => 'integer'],
            'inventory.default_warranty_months' => ['value' => $data['default_warranty_months'] ?? 12, 'group' => 'inventory', 'type' => 'integer'],
            'projects.default_delivery_days' => ['value' => $data['project_delivery_days'] ?? 45, 'group' => 'projects', 'type' => 'integer'],
            'projects.default_duration_days' => ['value' => $data['project_duration_days'] ?? 60, 'group' => 'projects', 'type' => 'integer'],
            'projects.milestone_sla_days' => ['value' => $data['project_milestone_sla_days'] ?? 7, 'group' => 'projects', 'type' => 'integer'],
            'tickets.default_sla_hours' => ['value' => $data['ticket_sla_hours'] ?? 48, 'group' => 'tickets', 'type' => 'integer'],
            'amc.default_sla_hours' => ['value' => $data['amc_sla_hours'] ?? 72, 'group' => 'amc', 'type' => 'integer'],
            'amc.renewal_reminder_days' => ['value' => $data['amc_renewal_days'] ?? 30, 'group' => 'amc', 'type' => 'integer'],
            'regional.locale' => ['value' => $data['locale'] ?? 'en_IN', 'group' => 'regional', 'type' => 'string'],
            'regional.timezone' => ['value' => $data['timezone'] ?? 'Asia/Kolkata', 'group' => 'regional', 'type' => 'string'],
            'regional.date_format' => ['value' => $data['date_format'] ?? 'Y-m-d', 'group' => 'regional', 'type' => 'string'],
            'regional.time_format' => ['value' => $data['time_format'] ?? 'H:i', 'group' => 'regional', 'type' => 'string'],
            'regional.currency_symbol' => ['value' => $data['currency_symbol'] ?? '₹', 'group' => 'regional', 'type' => 'string'],
            'regional.currency_precision' => ['value' => $data['currency_precision'] ?? 2, 'group' => 'regional', 'type' => 'integer'],
            'regional.measurement_units' => ['value' => $data['measurement_units'] ?? 'metric', 'group' => 'regional', 'type' => 'string'],
            'regional.rounding' => ['value' => $data['rounding_behavior'] ?? 'none', 'group' => 'regional', 'type' => 'string'],
            'branding.default_calling_code' => ['value' => $data['default_calling_code'] ?? '+91', 'group' => 'branding', 'type' => 'string'],
            'finance.fiscal_year_start_month' => ['value' => $data['fiscal_year_start_month'] ?? 4, 'group' => 'finance', 'type' => 'integer'],
            'finance.company_state' => ['value' => $data['company_state'] ?? 'Maharashtra', 'group' => 'finance', 'type' => 'string'],
            'finance.tax_split_rule' => ['value' => $data['tax_split_rule'] ?? 'auto', 'group' => 'finance', 'type' => 'string'],
            'finance.cgst_rate' => ['value' => $data['cgst_rate'] ?? 0.09, 'group' => 'finance', 'type' => 'float'],
            'finance.sgst_rate' => ['value' => $data['sgst_rate'] ?? 0.09, 'group' => 'finance', 'type' => 'float'],
            'finance.igst_rate' => ['value' => $data['igst_rate'] ?? 0.18, 'group' => 'finance', 'type' => 'float'],
            'finance.round_off' => ['value' => $data['finance_round_off'] ?? 'none', 'group' => 'finance', 'type' => 'string'],
            'quotations.validity_days' => ['value' => $data['quote_validity_days'] ?? 15, 'group' => 'quotations', 'type' => 'integer'],
            'quotations.default_terms' => ['value' => $data['quote_terms'] ?? null, 'group' => 'quotations', 'type' => 'string'],
            'quotations.qr_enabled' => ['value' => (bool) ($data['qr_enabled'] ?? true), 'group' => 'quotations', 'type' => 'boolean'],
            'documents.signature_block_enabled' => ['value' => (bool) ($data['signature_block_enabled'] ?? true), 'group' => 'documents', 'type' => 'boolean'],
            'documents.footer_block_enabled' => ['value' => (bool) ($data['footer_block_enabled'] ?? true), 'group' => 'documents', 'type' => 'boolean'],
            'documents.logo_on_pdf' => ['value' => (bool) ($data['logo_on_pdf'] ?? true), 'group' => 'documents', 'type' => 'boolean'],
            'api.enabled' => ['value' => (bool) ($data['api_enabled'] ?? false), 'group' => 'api', 'type' => 'boolean'],
            'exports.csv_enabled' => ['value' => (bool) ($data['export_csv_enabled'] ?? true), 'group' => 'exports', 'type' => 'boolean'],
            'exports.pdf_enabled' => ['value' => (bool) ($data['export_pdf_enabled'] ?? true), 'group' => 'exports', 'type' => 'boolean'],
            'system.backup_reminder_days' => ['value' => $data['backup_reminder_days'] ?? 7, 'group' => 'system', 'type' => 'integer'],
            'system.audit_retention_days' => ['value' => $data['retention_days'] ?? 365, 'group' => 'system', 'type' => 'integer'],
            'assignment.default_rule' => ['value' => $data['assignment_rule'] ?? 'manual', 'group' => 'assignment', 'type' => 'string'],
        ];

        $user = Auth::user();
        if ($user?->isSuperAdmin() || $user?->hasPermission('inventory.settings')) {
            $map['inventory.allow_negative_stock'] = [
                'value' => (bool) ($data['allow_negative_stock'] ?? false),
                'group' => 'inventory',
                'type' => 'boolean',
            ];
        }

        $map['calc.hybrid.peak_sun_hours'] = ['value' => $data['calc_psh'] ?? 5, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.performance_ratio'] = ['value' => $data['calc_pr'] ?? 0.75, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.system_loss_factor'] = ['value' => $data['calc_loss'] ?? 0, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.panel_wattage'] = ['value' => $data['calc_panel_w'] ?? 550, 'group' => 'calc', 'type' => 'integer'];
        $map['calc.hybrid.usable_dod'] = ['value' => $data['calc_dod'] ?? 0.9, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.battery_efficiency'] = ['value' => $data['calc_batt_eff'] ?? 0.95, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.inverter_efficiency'] = ['value' => $data['calc_inv_eff'] ?? 0.95, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.design_margin'] = ['value' => $data['calc_margin'] ?? 1.25, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.tariff'] = ['value' => $data['calc_tariff'] ?? 8, 'group' => 'calc', 'type' => 'float'];
        $map['calc.hybrid.export_rate'] = ['value' => $data['calc_export'] ?? 3, 'group' => 'calc', 'type' => 'float'];

        foreach ($map as $key => $meta) {
            $settings->set($key, $meta['value'], [
                'group' => $meta['group'],
                'type' => $meta['type'],
                'is_sample' => false,
                'change_summary' => "Updated {$key}",
            ], $userId);
        }

        app(NumberingService::class)->syncFromSettings('lead');
        app(NumberingService::class)->syncFromSettings('quote');

        // Keep Laravel app name in sync for non-Filament surfaces
        config(['app.name' => app(BrandingService::class)->appName()]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    private function normalizeUpload(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
