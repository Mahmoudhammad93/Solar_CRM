<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = [
        'uuid',
        'documentable_type',
        'documentable_id',
        'document_type',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'extension',
        'size_bytes',
        'is_sensitive',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $doc): void {
            if (! $doc->uuid) {
                $doc->uuid = (string) Str::uuid();
            }
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
