<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D2 contract: docs/AppFlow.md describes the end-to-end flow and the
 * separate state machines, using only rules the plan actually states.
 */
class AppFlowDocumentTest extends TestCase
{
    private string $flow;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/AppFlow.md';
        $this->assertFileExists($path, 'docs/AppFlow.md is required by D2');
        $this->flow = file_get_contents($path);
    }

    public function test_covers_the_end_to_end_stages_in_order(): void
    {
        $stages = ['Public request', 'Confirm', 'Check-in', 'Complete', 'Invoice', 'Payment', 'Cancel', 'Refund'];
        $last = -1;

        foreach ($stages as $stage) {
            $pos = strpos($this->flow, $stage);
            $this->assertNotFalse($pos, "Stage '$stage' missing");
            $this->assertGreaterThan($last, $pos, "Stage '$stage' is out of order");
            $last = $pos;
        }
    }

    public function test_refund_invoice_lifecycle_matches_the_plan(): void
    {
        $this->assertStringContainsString('draft → pending payout → completed', $this->flow);

        foreach (['failed', 'void'] as $terminal) {
            $this->assertStringContainsString($terminal, $this->flow);
        }
    }

    public function test_keeps_booking_invoice_payment_refund_and_notification_states_separate(): void
    {
        foreach (['Booking state', 'Invoice state', 'Payment state', 'Refund state', 'Notification state'] as $machine) {
            $this->assertStringContainsString($machine, $this->flow);
        }
    }

    public function test_states_the_failure_rules_from_the_plan(): void
    {
        foreach ([
            'does not reserve a slot',
            'at most one',
            'SMS',
            'stays confirmed',
            'old reminder',
            'idempotency',
            'version check',
        ] as $rule) {
            $this->assertStringContainsString($rule, $this->flow);
        }
    }

    public function test_includes_the_advance_worked_example(): void
    {
        foreach (['2,000', '2,500', '1,200', '700'] as $amount) {
            $this->assertStringContainsString($amount, $this->flow);
        }
    }

    public function test_does_not_invent_durations(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(minutes?|mins?|hours?|days?)\b/i', $this->flow);
    }

    public function test_links_back_to_the_prd_for_settings(): void
    {
        $this->assertStringContainsString('PRD.md', $this->flow);
        $this->assertStringContainsString('Unset', $this->flow);
    }
}
