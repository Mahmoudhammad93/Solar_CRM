<?php

namespace App\Filament\Pages;

use App\Models\ApiToken;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class ManageApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 97;

    protected static ?string $navigationLabel = 'API tokens';

    protected static ?string $title = 'API tokens';

    protected string $view = 'filament.pages.manage-api-tokens';

    public ?string $revealedPlainToken = null;

    public static function canAccess(): bool
    {
        return Gate::allows('settings.update');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ApiToken::query()->where('user_id', Filament::auth()->id())->orderByDesc('id'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('token_prefix')->label('Prefix')->formatStateUsing(fn (?string $state) => $state ? $state.'…' : '—'),
                TextColumn::make('abilities')->badge()->separator(','),
                TextColumn::make('last_used_at')->dateTime()->placeholder('Never'),
                TextColumn::make('revoked_at')->dateTime()->placeholder('Active')->label('Revoked'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create token')
                    ->form([
                        TextInput::make('name')->required()->maxLength(120),
                    ])
                    ->action(function (array $data): void {
                        if (! (bool) app(SettingsService::class)->get('api.enabled', false)) {
                            Notification::make()->title('Enable API in Settings → API & system first')->danger()->send();

                            return;
                        }
                        $issued = ApiToken::issue(Filament::auth()->user(), $data['name']);
                        $this->revealedPlainToken = $issued['plain'];
                        app(AuditLogger::class)->log(
                            action: 'api_token.created',
                            entityType: ApiToken::class,
                            entityId: $issued['token']->id,
                            entityReference: $issued['token']->token_prefix,
                            summary: ['name' => $data['name'], 'token' => $issued['plain']], // redacted in audit
                        );
                        Notification::make()
                            ->title('Token created — copy now; it will not be shown again')
                            ->warning()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->visible(fn (ApiToken $r) => $r->isActive())
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (ApiToken $record): void {
                        $record->revoke();
                        app(AuditLogger::class)->log(
                            action: 'api_token.revoked',
                            entityType: ApiToken::class,
                            entityId: $record->id,
                            entityReference: $record->token_prefix,
                        );
                        Notification::make()->title('Token revoked')->success()->send();
                    }),
            ]);
    }
}
