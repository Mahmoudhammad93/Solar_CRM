<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Collection;

class LeadDuplicateChecker
{
    /**
     * @return Collection<int, Lead>
     */
    public function findDuplicates(?string $mobile, ?string $email, ?int $ignoreLeadId = null): Collection
    {
        $mobile = $this->normalizePhone($mobile);
        $email = $this->normalizeEmail($email);

        if ($mobile === null && $email === null) {
            return collect();
        }

        $mobileTail = $mobile !== null ? substr($mobile, -10) : null;

        return Lead::query()
            ->when($ignoreLeadId, fn ($q) => $q->whereKeyNot($ignoreLeadId))
            ->when($email !== null, fn ($q) => $q->orWhereRaw('LOWER(email) = ?', [$email]))
            ->when($mobile !== null, function ($q) use ($mobile, $mobileTail) {
                // Broad fetch; precise match done in PHP for MySQL/SQLite portability.
                $q->orWhereNotNull('mobile');
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->filter(function (Lead $lead) use ($mobile, $mobileTail, $email) {
                $matchEmail = $email !== null
                    && $this->normalizeEmail($lead->email) === $email;

                $leadPhone = $this->normalizePhone($lead->mobile);
                $matchPhone = $mobile !== null && $leadPhone !== null && (
                    $leadPhone === $mobile
                    || ($mobileTail !== null && strlen($mobileTail) >= 8 && str_ends_with($leadPhone, $mobileTail))
                );

                return $matchEmail || $matchPhone;
            })
            ->take(10)
            ->values();
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return preg_replace('/\D+/', '', $phone) ?: null;
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }
}
