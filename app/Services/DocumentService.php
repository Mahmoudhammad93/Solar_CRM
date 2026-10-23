<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    /** @var array<string, list<string>> */
    public const ALLOWED_MIME_BY_EXT = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function maxUploadKb(): int
    {
        return (int) $this->settings->get('documents.max_upload_kb', 5120);
    }

    /**
     * @param  array{document_type?: string, is_sensitive?: bool}  $meta
     */
    public function store(Model $owner, UploadedFile $file, ?User $uploader = null, array $meta = []): Document
    {
        $this->assertValidUpload($file);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $storedName = Str::uuid()->toString().'.'.$ext;
        $directory = 'documents/'.strtolower(class_basename($owner)).'/'.$owner->getKey();
        $path = $file->storeAs($directory, $storedName, 'local');

        if (! $path) {
            throw ValidationException::withMessages(['file' => 'Upload failed.']);
        }

        $doc = Document::query()->create([
            'documentable_type' => $owner::class,
            'documentable_id' => $owner->getKey(),
            'document_type' => $meta['document_type'] ?? 'general',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'extension' => $ext,
            'size_bytes' => $file->getSize() ?: 0,
            'is_sensitive' => (bool) ($meta['is_sensitive'] ?? false),
            'uploaded_by' => $uploader?->id,
        ]);

        $this->auditLogger->log(
            action: 'document.uploaded',
            entityType: Document::class,
            entityId: $doc->id,
            entityReference: $doc->uuid,
            summary: [
                'original_name' => $doc->original_name,
                'document_type' => $doc->document_type,
                'owner' => class_basename($owner).'#'.$owner->getKey(),
            ],
        );

        return $doc;
    }

    public function assertValidUpload(UploadedFile $file): void
    {
        $maxKb = $this->maxUploadKb();
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => "File exceeds maximum size of {$maxKb} KB.",
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext === '' || ! array_key_exists($ext, self::ALLOWED_MIME_BY_EXT)) {
            throw ValidationException::withMessages([
                'file' => 'File extension is not allowed.',
            ]);
        }

        $detected = mime_content_type($file->getRealPath()) ?: $file->getMimeType();
        $allowed = self::ALLOWED_MIME_BY_EXT[$ext];

        if (! in_array($detected, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => "File content MIME ({$detected}) does not match extension .{$ext}.",
            ]);
        }

        // Extra guard: reject PHP-like payloads even if mime spoofed somehow
        $head = file_get_contents($file->getRealPath(), false, null, 0, 64) ?: '';
        if (preg_match('/<\?php|<\?=|<script/i', $head)) {
            throw ValidationException::withMessages([
                'file' => 'File content was rejected as potentially executable.',
            ]);
        }
    }

    public function download(Document $document): StreamedResponse
    {
        if (! Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'File missing.');
        }

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            ],
        );
    }

    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $uuid = $document->uuid;
        $document->delete();

        $this->auditLogger->log(
            action: 'document.deleted',
            entityType: Document::class,
            entityId: null,
            entityReference: $uuid,
            summary: ['original_name' => $document->original_name],
        );
    }
}
