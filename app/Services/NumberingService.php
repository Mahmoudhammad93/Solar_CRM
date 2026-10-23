<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NumberingService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function next(string $entity): string
    {
        return DB::transaction(function () use ($entity) {
            /** @var Sequence|null $sequence */
            $sequence = Sequence::query()->where('entity', $entity)->lockForUpdate()->first();

            if (! $sequence) {
                $prefix = strtoupper(substr($entity, 0, 3));
                $format = '{PREFIX}-{YYYY}-{####}';
                $resetYearly = false;

                try {
                    $prefix = (string) $this->settings->get("numbering.{$entity}.prefix", $prefix);
                    $format = (string) $this->settings->get("numbering.{$entity}.format", $format);
                    $resetYearly = (bool) $this->settings->get("numbering.{$entity}.reset_yearly", false);
                } catch (\Throwable) {
                    // Defaults when settings unavailable
                }

                $sequence = Sequence::query()->create([
                    'entity' => $entity,
                    'prefix' => $prefix,
                    'format' => $format,
                    'next_number' => 1,
                    'reset_yearly' => $resetYearly,
                    'last_reset_year' => null,
                ]);

                $sequence = Sequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            }

            $year = (int) now()->format('Y');

            if ($sequence->reset_yearly && $sequence->last_reset_year !== $year) {
                if ($sequence->last_reset_year !== null) {
                    $sequence->next_number = 1;
                }
                $sequence->last_reset_year = $year;
            }

            $number = (int) $sequence->next_number;
            if ($number < 1) {
                throw new RuntimeException("Invalid sequence state for {$entity}");
            }

            $sequence->next_number = $number + 1;
            $sequence->save();

            return $this->format($sequence, $number, $year);
        });
    }

    public function format(Sequence $sequence, int $number, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $padded = str_pad((string) $number, 4, '0', STR_PAD_LEFT);

        return str_replace(
            ['{PREFIX}', '{YYYY}', '{####}', '{###}', '{##}'],
            [$sequence->prefix, (string) $year, $padded, str_pad((string) $number, 3, '0', STR_PAD_LEFT), str_pad((string) $number, 2, '0', STR_PAD_LEFT)],
            $sequence->format,
        );
    }

    public function syncFromSettings(string $entity): Sequence
    {
        $prefix = strtoupper(substr($entity, 0, 3));
        $format = '{PREFIX}-{YYYY}-{####}';
        $reset = false;

        try {
            $prefix = (string) $this->settings->get("numbering.{$entity}.prefix", $prefix);
            $format = (string) $this->settings->get("numbering.{$entity}.format", $format);
            $reset = (bool) $this->settings->get("numbering.{$entity}.reset_yearly", false);
        } catch (\Throwable) {
            // Settings may be unavailable in narrow test bootstraps; defaults remain.
        }

        $sequence = Sequence::query()->firstOrNew(['entity' => $entity]);
        $sequence->prefix = $prefix;
        $sequence->format = $format;
        $sequence->reset_yearly = $reset;
        if (! $sequence->exists) {
            $sequence->next_number = 1;
        }
        $sequence->save();

        return $sequence;
    }
}
