<?php

namespace App\Models;

use App\Support\RecordScoping;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Privileged auth fields (role_id, is_active) are intentionally omitted so
     * request-derived mass assignment cannot escalate privileges. Seeders,
     * factories, and admin code must use forceFill() / unguarded for those.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'branch_id',
        'team_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === 'super-admin';
    }

    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->role;
        if (! $role) {
            return false;
        }

        return $role->hasPermissionKey($key);
    }

    public function canModule(string $module, string $action): bool
    {
        return $this->hasPermission("{$module}.{$action}");
    }

    public function mustScopeToAssignedRecords(): bool
    {
        return RecordScoping::mustScopeToAssigned($this);
    }
}
