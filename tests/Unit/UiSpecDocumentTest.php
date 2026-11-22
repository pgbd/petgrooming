<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D3 contract: docs/UI_Spec.md specifies every required screen in every
 * required state, and only from rules already in the PRD and AppFlow.
 */
class UiSpecDocumentTest extends TestCase
{
    private const SCREENS = ['Booking confirm', 'Daily close', 'Refund Invoice', 'Admin Settings'];

    private const STATES = ['Normal', 'Empty', 'Loading', 'Validation', 'Conflict', 'Permission', 'Failure'];

    private string $spec;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/UI_Spec.md';
        $this->assertFileExists($path, 'docs/UI_Spec.md is required by D3');
        $this->spec = file_get_contents($path);
    }

    private function section(string $screen): string
    {
        $parts = preg_split('/^## /m', $this->spec);

        foreach ($parts as $part) {
            if (str_starts_with($part, $screen)) {
                return $part;
            }
        }

        $this->fail("Section '$screen' not found");
    }

    public function test_every_screen_has_its_own_section(): void
    {
        foreach (self::SCREENS as $screen) {
            $this->assertNotEmpty($this->section($screen));
        }
    }

    public function test_every_screen_defines_all_seven_states(): void
    {
        foreach (self::SCREENS as $screen) {
            $section = $this->section($screen);

            foreach (self::STATES as $state) {
                $this->assertMatchesRegularExpression(
                    '/^\| '.$state.' \|/m',
                    $section,
                    "$screen is missing the $state state"
                );
            }
        }
    }

    public function test_conflict_states_cover_the_plan_scenarios(): void
    {
        $this->assertStringContainsString('another user confirmed', strtolower($this->section('Booking confirm')));
        $this->assertStringContainsString('version', strtolower($this->section('Refund Invoice')));
    }

    public function test_unset_settings_block_visibly_instead_of_defaulting(): void
    {
        $this->assertStringContainsString('Unset', $this->section('Booking confirm'));
        $this->assertStringContainsString('Unset', $this->section('Admin Settings'));
    }

    public function test_refund_screen_requires_an_active_reason(): void
    {
        $refund = $this->section('Refund Invoice');

        $this->assertStringContainsString('active reason', $refund);
        $this->assertStringContainsString('archived', $refund);
    }

    public function test_daily_close_compares_expected_with_declared(): void
    {
        $close = $this->section('Daily close');

        $this->assertStringContainsString('expected', strtolower($close));
        $this->assertStringContainsString('declared', strtolower($close));
        $this->assertStringContainsString('reason', strtolower($close));
    }

    public function test_partial_completion_is_specified_for_booking_confirm(): void
    {
        $this->assertStringContainsString('SMS', $this->section('Booking confirm'));
    }

    public function test_does_not_invent_durations_or_fees(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(minutes?|mins?|seconds?|hours?|days?)\b/i', $this->spec);
        $this->assertDoesNotMatchRegularExpression('/(৳|BDT|Tk\.?)\s*\d/u', $this->spec);
    }

    public function test_links_to_the_upstream_documents(): void
    {
        $this->assertStringContainsString('PRD.md', $this->spec);
        $this->assertStringContainsString('AppFlow.md', $this->spec);
    }
}
