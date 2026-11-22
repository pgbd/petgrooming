<?php

namespace Tests\Concurrency;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Spike S-2: two parallel confirms on one seeded resource, on real MariaDB.
 *
 * Each "confirm" runs in its own PHP process so the two transactions really
 * overlap. Runs only against a database whose name ends in _spike, because it
 * creates and drops its own scratch tables.
 */
class ConfirmLockSpikeTest extends TestCase
{
    private string $barrier;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mariadb') {
            $this->markTestSkipped('S-2 needs a real MariaDB connection (set DB_CONNECTION=mariadb).');
        }

        $database = (string) config('database.connections.mariadb.database');
        $this->assertStringEndsWith('_spike', $database, 'Refusing to create scratch tables outside a *_spike database');

        Schema::dropIfExists('spike_appointments');
        Schema::dropIfExists('spike_resources');

        Schema::create('spike_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('spike_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('resource_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('label');
        });

        DB::table('spike_resources')->insert([['name' => 'groomer-1'], ['name' => 'groomer-2']]);

        $this->barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'s2-'.bin2hex(random_bytes(4));
        mkdir($this->barrier);
    }

    protected function tearDown(): void
    {
        if (config('database.default') === 'mariadb') {
            Schema::dropIfExists('spike_appointments');
            Schema::dropIfExists('spike_resources');
        }

        if (isset($this->barrier) && is_dir($this->barrier)) {
            array_map('unlink', glob($this->barrier.DIRECTORY_SEPARATOR.'*') ?: []);
            rmdir($this->barrier);
        }

        parent::tearDown();
    }

    /**
     * Start every job as its own process, release them together, collect results.
     *
     * @param  array<string, list<string>>  $jobs  label => worker arguments
     * @return array<string, array{status: string, attempts: int}>
     */
    private function runTogether(array $jobs): array
    {
        $worker = __DIR__.DIRECTORY_SEPARATOR.'worker.php';
        $this->assertFileExists($worker, 'The parallel worker script is required');
        $processes = [];

        foreach ($jobs as $label => $args) {
            $command = array_merge([PHP_BINARY, $worker, 'label='.$label, 'barrier='.$this->barrier], $args);
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            $this->assertIsResource($process, "Could not start worker $label");
            $processes[$label] = [$process, $pipes];
        }

        $deadline = microtime(true) + 60;
        foreach (array_keys($jobs) as $label) {
            while (! is_file($this->barrier.DIRECTORY_SEPARATOR."ready-$label")) {
                $this->assertLessThan($deadline, microtime(true), "Worker $label never became ready");
                usleep(20_000);
            }
        }

        touch($this->barrier.DIRECTORY_SEPARATOR.'go');

        $results = [];
        foreach ($processes as $label => [$process, $pipes]) {
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            proc_close($process);

            $lines = array_values(array_filter(explode("\n", trim($stdout))));
            $decoded = json_decode((string) end($lines), true);
            $this->assertIsArray($decoded, "Worker $label gave no result. stdout: $stdout stderr: $stderr");
            $results[$label] = $decoded;
        }

        return $results;
    }

    /** @param  array<string, array{status: string, attempts: int}>  $results */
    private function statuses(array $results): array
    {
        $statuses = array_column($results, 'status');
        sort($statuses);

        return $statuses;
    }

    private function resetAppointments(): void
    {
        DB::table('spike_appointments')->delete();
    }

    public function test_two_parallel_confirms_for_the_same_slot_yield_exactly_one_booking(): void
    {
        foreach (range(1, 8) as $round) {
            $this->resetAppointments();
            $this->barrier = $this->freshBarrier();

            $results = $this->runTogether([
                'a' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
                'b' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
            ]);

            $this->assertSame(['confirmed', 'conflict'], $this->statuses($results), "Round $round");
            $this->assertSame(1, DB::table('spike_appointments')->count(), "Round $round must leave exactly one booking");
        }
    }

    public function test_a_partly_overlapping_slot_conflicts(): void
    {
        $results = $this->runTogether([
            'a' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
            'b' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:30:00', 'end=2030-01-01 11:30:00', 'hold=150'],
        ]);

        $this->assertSame(['confirmed', 'conflict'], $this->statuses($results));
        $this->assertSame(1, DB::table('spike_appointments')->count());
    }

    public function test_adjacent_slots_do_not_conflict_because_intervals_are_half_open(): void
    {
        $results = $this->runTogether([
            'a' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
            'b' => ['mode=slot', 'resource=1', 'start=2030-01-01 11:00:00', 'end=2030-01-01 12:00:00', 'hold=150'],
        ]);

        $this->assertSame(['confirmed', 'confirmed'], $this->statuses($results));
        $this->assertSame(2, DB::table('spike_appointments')->count());
    }

    public function test_different_resources_book_the_same_time_independently(): void
    {
        $results = $this->runTogether([
            'a' => ['mode=slot', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
            'b' => ['mode=slot', 'resource=2', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=150'],
        ]);

        $this->assertSame(['confirmed', 'confirmed'], $this->statuses($results));
    }

    public function test_negative_control_without_the_row_lock_the_race_double_books(): void
    {
        $results = $this->runTogether([
            'a' => ['mode=slot', 'lock=0', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=300'],
            'b' => ['mode=slot', 'lock=0', 'resource=1', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00', 'hold=300'],
        ]);

        $this->assertSame(['confirmed', 'confirmed'], $this->statuses($results), 'The control must show the race, or the other tests prove nothing');
        $this->assertSame(2, DB::table('spike_appointments')->count());
    }

    public function test_a_deadlock_is_retried_and_both_transactions_complete(): void
    {
        $results = $this->runTogether([
            'a' => ['mode=swap', 'first=1', 'second=2', 'start=2030-01-01 10:00:00', 'end=2030-01-01 11:00:00'],
            'b' => ['mode=swap', 'first=2', 'second=1', 'start=2030-01-01 12:00:00', 'end=2030-01-01 13:00:00'],
        ]);

        $this->assertSame(['confirmed', 'confirmed'], $this->statuses($results));
        $this->assertGreaterThanOrEqual(3, array_sum(array_column($results, 'attempts')), 'One side must have been retried after the deadlock');
        $this->assertSame(2, DB::table('spike_appointments')->count());
    }

    private function freshBarrier(): string
    {
        array_map('unlink', glob($this->barrier.DIRECTORY_SEPARATOR.'*') ?: []);

        return $this->barrier;
    }
}
