<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_number',
        'customer_type',
        'name',
        'company_name',
        'contact_person',
        'mobile',
        'alternate_phone',
        'email',
        'address',
        'city',
        'district',
        'state',
        'postal_code',
        'gps_lat',
        'gps_lng',
        'branch_id',
        'notes',
        'whatsapp_consent_status',
        'whatsapp_consent_at',
        'whatsapp_consent_source',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_consent_at' => 'datetime',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
        ];
    }

    public function recordWhatsAppConsent(string $status, ?string $source = null): void
    {
        $normalized = strtolower($status);
        if (! in_array($normalized, ['opted_in', 'opted_out', 'unknown'], true)) {
            $normalized = 'unknown';
        }

        $this->forceFill([
            'whatsapp_consent_status' => $normalized,
            'whatsapp_consent_at' => now(),
            'whatsapp_consent_source' => $source,
        ])->save();
    }

    public function hasWhatsAppOptIn(): bool
    {
        return strtolower((string) $this->whatsapp_consent_status) === 'opted_in';
    }

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            if ($customer->isDirty('whatsapp_consent_status')) {
                $customer->whatsapp_consent_at = now();
                if (! filled($customer->whatsapp_consent_source)) {
                    $customer->whatsapp_consent_source = 'manual';
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
