<?php

namespace App\Filament\Resources\Visits\RelationManagers;

use App\Models\Document;
use App\Models\Lookup;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents / photos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('document_type')
                ->options(fn () => Lookup::options('document_type'))
                ->required(),
            Toggle::make('is_sensitive')->label('Sensitive (role-restricted)'),
            FileUpload::make('upload')
                ->label('File')
                ->required()
                ->disk('local')
                ->directory('tmp-uploads')
                ->visibility('private')
                ->maxSize(app(DocumentService::class)->maxUploadKb())
                ->acceptedFileTypes([
                    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                    'application/pdf',
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')->searchable(),
                TextColumn::make('document_type')->badge(),
                TextColumn::make('mime_type')->toggleable(),
                TextColumn::make('size_bytes')->label('Size')->formatStateUsing(fn ($state) => number_format(((int) $state) / 1024, 1).' KB'),
                IconColumn::make('is_sensitive')->boolean(),
                TextColumn::make('uploader.name')->label('Uploader'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload')
                    ->using(function (array $data, string $model): Model {
                        /** @var \App\Models\Visit $visit */
                        $visit = $this->getOwnerRecord();
                        $path = is_array($data['upload'] ?? null) ? ($data['upload'][0] ?? null) : ($data['upload'] ?? null);
                        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
                            Notification::make()->title('Upload missing')->danger()->send();
                            abort(422);
                        }

                        $full = Storage::disk('local')->path($path);
                        $detectedMime = mime_content_type($full) ?: 'application/octet-stream';
                        $ext = pathinfo($path, PATHINFO_EXTENSION)
                            ?: match ($detectedMime) {
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/webp' => 'webp',
                                'image/gif' => 'gif',
                                'application/pdf' => 'pdf',
                                default => 'bin',
                            };
                        $originalName = pathinfo($path, PATHINFO_FILENAME).'.'.$ext;
                        $uploaded = new \Illuminate\Http\UploadedFile(
                            $full,
                            $originalName,
                            $detectedMime,
                            null,
                            true,
                        );

                        $doc = app(DocumentService::class)->store($visit, $uploaded, auth()->user(), [
                            'document_type' => $data['document_type'] ?? 'general',
                            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
                        ]);

                        Storage::disk('local')->delete($path);

                        return $doc;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->url(fn (Document $record): string => route('documents.download', $record->uuid))
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => Gate::allows('download', $record)),
                DeleteAction::make()
                    ->using(function (Document $record): void {
                        Gate::authorize('delete', $record);
                        app(DocumentService::class)->delete($record);
                    }),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
