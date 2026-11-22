<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Each contradiction C1..C7 is closed by an accepted decision record.
 */
class DecisionRecordsTest extends TestCase
{
    private function record(int $n): string
    {
        $files = glob(dirname(__DIR__, 2).'/docs/decisions/'.sprintf('%04d', $n).'-*.md');
        $this->assertNotEmpty($files, 'ADR-'.sprintf('%04d', $n).' is missing');

        return file_get_contents($files[0]);
    }

    public function test_every_contradiction_has_an_accepted_record(): void
    {
        foreach (range(1, 7) as $n) {
            $adr = $this->record($n);

            foreach (['Status: Accepted', '## Context', '## Decision', '## Consequences', "Resolves contradiction C$n"] as $needle) {
                $this->assertStringContainsString($needle, $adr, "ADR-$n lacks '$needle'");
            }
        }
    }

    public function test_records_state_who_approved_and_that_g0_still_applies(): void
    {
        foreach (range(1, 7) as $n) {
            $adr = $this->record($n);

            $this->assertStringContainsString('Approved', $adr);
            $this->assertStringContainsString('G0', $adr);
        }
    }

    public function test_specific_decisions_are_recorded(): void
    {
        $this->assertStringContainsString('Laragon', $this->record(1));
        $this->assertStringContainsString('11.4', $this->record(1));
        $this->assertStringContainsString('roles', $this->record(2));
        $this->assertStringContainsString('booking, invoice, payment', $this->record(3));
        $this->assertStringContainsString('M2', $this->record(4));
        $this->assertStringContainsString('develop', $this->record(5));
        $this->assertStringContainsString('S-2', $this->record(6));
        $this->assertStringContainsString('Phase A', $this->record(7));
    }
}
