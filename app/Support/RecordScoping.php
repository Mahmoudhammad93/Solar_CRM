<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable record-level query scoping for domain modules.
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

    public static function isSalesManager(?User $user): bool
    {
        return $user?->role?->slug === 'sales-manager';
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyAssignedScope(Builder $query, ?User $user, string $ownerColumn = 'assigned_to'): Builder
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if (self::isSalesManager($user)) {
            return self::applyTeamOwnerScope($query, $user, $ownerColumn);
        }

        if (self::mustScopeToAssigned($user)) {
            return $query->where($ownerColumn, $user->id);
        }

        return $query;
    }

    /**
     * Sales Manager: records owned by anyone on the same team (fallback: same branch).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyTeamOwnerScope(Builder $query, User $user, string $ownerColumn = 'assigned_to'): Builder
    {
        $ownerIds = User::query()
            ->when(
                $user->team_id,
                fn (Builder $q) => $q->where('team_id', $user->team_id),
                fn (Builder $q) => $q->when(
                    $user->branch_id,
                    fn (Builder $inner) => $inner->where('branch_id', $user->branch_id),
                    fn (Builder $inner) => $inner->whereKey($user->id),
                ),
            )
            ->pluck('id')
            ->all();

        if ($ownerIds === []) {
            $ownerIds = [$user->id];
        }

        return $query->whereIn($ownerColumn, $ownerIds);
    }

    public static function canAccessOwnedRecord(?User $user, ?int $ownerId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (self::isSalesManager($user)) {
            if ($ownerId === null) {
                return true;
            }

            $allowed = User::query()
                ->when(
                    $user->team_id,
                    fn (Builder $q) => $q->where('team_id', $user->team_id),
                    fn (Builder $q) => $q->when(
                        $user->branch_id,
                        fn (Builder $inner) => $inner->where('branch_id', $user->branch_id),
                        fn (Builder $inner) => $inner->whereKey($user->id),
                    ),
                )
                ->whereKey($ownerId)
                ->exists();

            return $allowed;
        }

        if (! self::mustScopeToAssigned($user)) {
            return true;
        }

        return $ownerId !== null && (int) $ownerId === (int) $user->id;
    }
}
