<?php

/**
 * One parallel participant for the S-2 spike. Boots the real application,
 * announces it is ready, waits for the shared "go" file, runs one scenario and
 * prints a single JSON line.
 *
 * Arguments are key=value pairs: label, barrier, mode (slot|swap), and the
 * scenario's own keys.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Tests\Concurrency\Support\Confirm;

require __DIR__.'/../../vendor/autoload.php';

$args = [];
foreach (array_slice($argv, 1) as $pair) {
    [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
    $args[$key] = $value;
}

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

DB::select('select 1');

$barrier = $args['barrier'];
$label = $args['label'];

touch($barrier.DIRECTORY_SEPARATOR."ready-$label");
Confirm::waitFor($barrier.DIRECTORY_SEPARATOR.'go');

try {
    $result = match ($args['mode']) {
        'slot' => Confirm::slot(
            (int) $args['resource'],
            $args['start'],
            $args['end'],
            $label,
            lock: ($args['lock'] ?? '1') !== '0',
            holdMs: (int) ($args['hold'] ?? 0),
        ),
        'swap' => Confirm::swap(
            (int) $args['first'],
            (int) $args['second'],
            $args['start'],
            $args['end'],
            $label,
            $barrier,
        ),
        default => ['status' => 'error', 'attempts' => 0],
    };
} catch (Throwable $e) {
    $result = ['status' => 'error', 'attempts' => 0, 'error' => $e->getMessage()];
}

echo json_encode($result), "\n";
