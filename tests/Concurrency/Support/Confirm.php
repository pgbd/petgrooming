<?php

namespace Tests\Concurrency\Support;

use Illuminate\Support\Facades\DB;

/**
 * The booking-confirm pattern from the TRD, written once so the spike can
 * exercise it: a transaction, a row lock on one concrete parent row, a
 * half-open [start, end) overlap check, and a bounded retry count.
 *
 * This is spike code. The production ConfirmAppointment action (BK-2) is
 * built test-first in M2 and must pass the same scenarios.
 */
final class Confirm
{
    /**
     * @return array{status: string, attempts: int}
     */
    public static function slot(
        int $resourceId,
        string $start,
        string $end,
        string $label,
        bool $lock = true,
        int $holdMs = 0,
        int $maxAttempts = 3,
    ): array {
        $attempts = 0;

        try {
            DB::transaction(function () use ($resourceId, $start, $end, $label, $lock, $holdMs, &$attempts): void {
                $attempts++;

                if ($lock) {
                    DB::table('spike_resources')->where('id', $resourceId)->lockForUpdate()->first();
                }

                $overlaps = DB::table('spike_appointments')
                    ->where('resource_id', $resourceId)
                    ->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start)
                    ->exists();

                if ($overlaps) {
                    throw new SlotTaken;
                }

                if ($holdMs > 0) {
                    usleep($holdMs * 1000);
                }

                DB::table('spike_appointments')->insert([
                    'resource_id' => $resourceId,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'label' => $label,
                ]);
            }, $maxAttempts);
        } catch (SlotTaken) {
            return ['status' => 'conflict', 'attempts' => $attempts];
        }

        return ['status' => 'confirmed', 'attempts' => $attempts];
    }

    /**
     * Lock two resources in the given order. Two callers using opposite orders
     * deadlock; Laravel's transaction retry must let both finish.
     *
     * @return array{status: string, attempts: int}
     */
    public static function swap(
        int $first,
        int $second,
        string $start,
        string $end,
        string $label,
        string $barrier,
        int $maxAttempts = 3,
    ): array {
        $attempts = 0;

        DB::transaction(function () use ($first, $second, $start, $end, $label, $barrier, &$attempts): void {
            $attempts++;

            DB::table('spike_resources')->where('id', $first)->lockForUpdate()->first();

            if ($attempts === 1) {
                touch($barrier.DIRECTORY_SEPARATOR."locked-$label");
                self::waitFor($barrier.DIRECTORY_SEPARATOR.'locked-'.($label === 'a' ? 'b' : 'a'));
            }

            DB::table('spike_resources')->where('id', $second)->lockForUpdate()->first();

            DB::table('spike_appointments')->insert([
                'resource_id' => $second,
                'starts_at' => $start,
                'ends_at' => $end,
                'label' => $label,
            ]);
        }, $maxAttempts);

        return ['status' => 'confirmed', 'attempts' => $attempts];
    }

    public static function waitFor(string $file, int $seconds = 30): void
    {
        $deadline = microtime(true) + $seconds;

        while (! is_file($file)) {
            if (microtime(true) > $deadline) {
                throw new \RuntimeException("Timed out waiting for $file");
            }

            usleep(2_000);
        }
    }
}
