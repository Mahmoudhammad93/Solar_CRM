<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable record-level query scoping for later domain modules.
 * Sales Executive and Surveyor/Engineer see only assigned records by default.
 */
final class RecordScoping
{
    public const ASSIGNED_ROLE_SLUGS = [
        'sales-executive',
        'surveyor-engineer',
    ];

    public static function mustScopeToAssigned(?User $user): bool
    {
        if (! $user || $user->isSuperAdmin()) {
            return false;
        }

        $slug = $user->role?->slug;

        return $slug !== null && in_array($slug, self::ASSIGNED_ROLE_SLUGS, true);
    }

    /**
     * Apply owner/assignee scoping. Column defaults are common for leads/visits.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyAssignedScope(Builder $query, ?User $user, string $ownerColumn = 'assigned_to'): Builder
    {
        if (! self::mustScopeToAssigned($user)) {
            return $query;
        }

        return $query->where($ownerColumn, $user->id);
    }

    /**
     * Whether a user may access a specific owned record.
     */
    public static function canAccessOwnedRecord(?User $user, ?int $ownerId): bool
    {
        if (! $user) {
            return false;
        }

        if (! self::mustScopeToAssigned($user)) {
            return true;
        }

        return $ownerId !== null && (int) $ownerId === (int) $user->id;
    }
}
