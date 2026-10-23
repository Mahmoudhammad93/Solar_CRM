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
                Tab::make('Coming soon')->schema([
                    Section::make('Not yet implemented')
                        ->schema([
                            Placeholder::make('unavailable_lists')
                                ->label('Dynamic lists / workflow')
                                ->content('Unavailable — Phase 2+'),
                            Placeholder::make('unavailable_calc')
                                ->label('Calculator assumptions')
                                ->content('Unavailable — calculator phases'),
                            Placeholder::make('unavailable_whatsapp')
                                ->label('WhatsApp / Email integrations')
                                ->content('Unavailable — Demo drivers in later phases'),
                            Placeholder::make('unavailable_regional')
                                ->label('Regional / finance / API')
                                ->content('Unavailable — later settings phases'),
                        ]),
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
        ];

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
