<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D1 contract: docs/PRD.md records owner decisions faithfully and never
 * invents values the owner has not set.
 */
class PrdDocumentTest extends TestCase
{
    private string $prd;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/PRD.md';
        $this->assertFileExists($path, 'docs/PRD.md is required by D1');
        $this->prd = file_get_contents($path);
    }

    public function test_records_every_owner_confirmed_baseline_answer(): void
    {
        foreach (range(1, 8) as $n) {
            $this->assertStringContainsString("OWN-P$n", $this->prd);
        }

        $this->assertStringContainsString('2 branches', $this->prd);
        $this->assertStringContainsString('2 admins', $this->prd);
        $this->assertStringContainsString('4 staff', $this->prd);
    }

    public function test_flags_that_official_own_ids_are_pending_the_architecture_note(): void
    {
        $this->assertStringContainsString('OWN-001', $this->prd);
        $this->assertStringContainsString('Architecture Revision Note', $this->prd);
        $this->assertStringContainsString('pending', strtolower($this->prd));
    }

    public function test_settings_the_owner_left_dynamic_are_marked_unset(): void
    {
        foreach (['Slot block duration', 'Final price approver', 'Home service coverage', 'Cancellation'] as $setting) {
            $this->assertMatchesRegularExpression('/'.preg_quote($setting, '/').'.*Unset/i', $this->prd, "$setting must be marked Unset");
        }
    }

    public function test_does_not_invent_durations_or_fees(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(minutes?|mins?|hours?)\b/i', $this->prd);
        $this->assertDoesNotMatchRegularExpression('/(৳|BDT|Tk\.?)\s*\d/u', $this->prd);
    }

    public function test_lists_the_open_decisions_with_their_decider(): void
    {
        foreach (['Owner', 'Accountant', 'Vaiya'] as $decider) {
            $this->assertStringContainsString($decider, $this->prd);
        }
        $this->assertStringContainsString('Mushak', $this->prd);
    }

    public function test_declares_mvp_exclusions(): void
    {
        foreach (['customer login', 'Flutter', 'payment gateway'] as $excluded) {
            $this->assertStringContainsString($excluded, $this->prd);
        }
    }
}
