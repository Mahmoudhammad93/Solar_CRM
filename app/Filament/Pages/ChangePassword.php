<?php

namespace App\Filament\Pages;

use App\Services\AuditLogger;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ChangePassword extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Change password';

    protected static ?string $title = 'Change password';

    protected string $view = 'filament.pages.change-password';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $min = (int) app(SettingsService::class)->get('security.password_min_length', 8);

        return $schema->components([
            Section::make('Password')
                ->description("Policy: at least {$min} characters, mixed case, and at least one number.")
                ->schema([
                    TextInput::make('current_password')
                        ->label('Current password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->autocomplete('current-password'),
                    TextInput::make('password')
                        ->label('New password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::min($min)->mixedCase()->numbers())
                        ->autocomplete('new-password'),
                    TextInput::make('password_confirmation')
                        ->label('Confirm new password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('password')
                        ->autocomplete('new-password'),
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
                            ->label('Update password')
                            ->submit('save'),
                        Action::make('cancel')
                            ->label('Cancel')
                            ->color('gray')
                            ->action(fn () => $this->form->fill()),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();
        abort_unless($user !== null, 403);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'Current password is incorrect.',
            ]);
        }

        if (($data['password'] ?? null) !== ($data['password_confirmation'] ?? null)) {
            throw ValidationException::withMessages([
                'data.password_confirmation' => 'New password confirmation does not match.',
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        app(AuditLogger::class)->log(
            action: 'user.password_changed',
            entityType: $user::class,
            entityId: $user->id,
            entityReference: $user->email,
            summary: [
                'user_id' => $user->id,
                'password' => 'should-never-appear',
                'note' => 'Password changed by account owner',
            ],
        );

        $this->form->fill();

        Notification::make()
            ->title('Password updated')
            ->body('Your new password is now active. The previous password will no longer work.')
            ->success()
            ->send();
    }
}
