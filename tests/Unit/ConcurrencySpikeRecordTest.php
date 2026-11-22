<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Spike S-2 contract: the result is written down faithfully, including what it
 * does not prove, and the concurrency suite is wired into CI on real MariaDB.
 */
class ConcurrencySpikeRecordTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function test_the_s2_record_states_scenarios_and_limits(): void
    {
        $path = $this->root.'/docs/spikes/S-2.md';
        $this->assertFileExists($path, 'The S-2 result must be written down');
        $doc = file_get_contents($path);

        foreach ([
            'MariaDB 11.4',
            'exactly one',
            'half-open',
            'deadlock',
            'negative control',
            'REPEATABLE-READ',
            'Gate 1',
            'not verified',
            'ConfirmAppointment',
        ] as $needle) {
            $this->assertStringContainsString($needle, $doc, "S-2.md lacks '$needle'");
        }
    }

    public function test_the_concurrency_suite_is_part_of_phpunit(): void
    {
        $this->assertStringContainsString('tests/Concurrency', file_get_contents($this->root.'/phpunit.xml'));
    }

    public function test_ci_runs_the_concurrency_suite_against_a_mariadb_11_4_service(): void
    {
        $ci = file_get_contents($this->root.'/.github/workflows/ci.yml');

        $this->assertStringContainsString('image: mariadb:11.4', $ci);
        $this->assertStringContainsString('DB_CONNECTION: mariadb', $ci);
        $this->assertStringContainsString('tests/Concurrency', $ci);
        $this->assertStringContainsString('petgrooming_spike', $ci);
    }
}
