<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D6 contract: docs/Plan.md is a dependency-ordered backlog that covers
 * every gate, hides no contradiction, and promises no calendar dates.
 */
class PlanDocumentTest extends TestCase
{
    private string $plan;

    /** @var array<string, array{task:string, depends:string, verify:string, gates:string}> */
    private array $tasks = [];

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/Plan.md';
        $this->assertFileExists($path, 'docs/Plan.md is required by D6');
        $this->plan = file_get_contents($path);

        preg_match_all('/^\| ([A-Z]+-?\d+) \| (.+?) \| (.+?) \| (.+?) \| (.+?) \|$/m', $this->plan, $rows, PREG_SET_ORDER);

        foreach ($rows as $r) {
            $this->tasks[$r[1]] = ['task' => $r[2], 'depends' => $r[3], 'verify' => $r[4], 'gates' => $r[5]];
        }
    }

    public function test_every_milestone_has_a_section(): void
    {
        foreach (range(0, 6) as $m) {
            $this->assertMatchesRegularExpression('/^## M'.$m.'\b/m', $this->plan, "Milestone M$m missing");
        }
    }

    public function test_every_task_has_dependencies_a_verify_step_and_gates(): void
    {
        $this->assertGreaterThanOrEqual(30, count($this->tasks), 'Backlog looks too thin');

        foreach ($this->tasks as $id => $t) {
            foreach (['task', 'depends', 'verify', 'gates'] as $field) {
                $this->assertNotSame('', trim($t[$field]), "$id has an empty $field");
            }
        }
    }

    public function test_every_dependency_points_to_a_real_earlier_task(): void
    {
        $seen = [];

        foreach ($this->tasks as $id => $t) {
            if (trim($t['depends']) !== '—') {
                foreach (array_map('trim', explode(',', $t['depends'])) as $dep) {
                    $this->assertArrayHasKey($dep, $this->tasks, "$id depends on unknown $dep");
                    $this->assertContains($dep, $seen, "$id depends on $dep, which is listed later");
                }
            }
            $seen[] = $id;
        }
    }

    public function test_every_gate_is_defined_and_covered_by_a_task(): void
    {
        $covered = [];

        foreach ($this->tasks as $t) {
            foreach (preg_split('/\s*,\s*/', trim($t['gates'])) as $g) {
                if (ctype_digit($g)) {
                    $covered[(int) $g] = true;
                }
            }
        }

        foreach (range(1, 20) as $gate) {
            $this->assertMatchesRegularExpression('/^\| '.$gate.' \|/m', $this->plan, "Gate $gate is not defined");
            $this->assertArrayHasKey($gate, $covered, "Gate $gate is covered by no task");
        }
    }

    public function test_spikes_and_phase_a_steps_are_tasks(): void
    {
        foreach (['A0', 'D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'S-0', 'S-1', 'S-2', 'S-3', 'S-4', 'S-5', 'S-6', 'S-7'] as $id) {
            $this->assertArrayHasKey($id, $this->tasks, "$id missing from the backlog");
        }
    }

    public function test_exit_gates_g0_and_g1_block_later_work(): void
    {
        $this->assertStringContainsString('G0', $this->plan);
        $this->assertStringContainsString('G1', $this->plan);
        $this->assertMatchesRegularExpression('/code.*not.*start|no code/i', $this->plan);
    }

    public function test_contradictions_are_listed_and_left_unresolved(): void
    {
        $this->assertMatchesRegularExpression('/^## Contradictions/m', $this->plan);
        $this->assertGreaterThanOrEqual(5, preg_match_all('/^\| C\d+ \|.*\| Unresolved \|$/m', $this->plan));
    }

    public function test_lists_the_open_decisions_by_reference_to_the_prd(): void
    {
        $this->assertMatchesRegularExpression('/^## Open decisions/m', $this->plan);
        $this->assertStringContainsString('PRD.md', $this->plan);
    }

    public function test_traceability_to_own_and_r_ids_is_flagged_pending(): void
    {
        $this->assertStringContainsString('OWN-001', $this->plan);
        $this->assertStringContainsString('R01', $this->plan);
        $this->assertStringContainsString('pending', strtolower($this->plan));
    }

    public function test_promises_no_calendar_dates_or_durations(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(minutes?|hours?|days?|weeks?|months?)\b/i', $this->plan);
        $this->assertDoesNotMatchRegularExpression('/\b(20\d\d-\d\d-\d\d)\b/', $this->plan);
        $this->assertStringContainsString('order, not a schedule', strtolower($this->plan));
    }
}
