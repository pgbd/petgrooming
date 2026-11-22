<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D4 contract: docs/TRD.md records the architecture and engineering rules
 * from the plan, and leaves unproven numbers and versions unset.
 */
class TrdDocumentTest extends TestCase
{
    private string $trd;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/TRD.md';
        $this->assertFileExists($path, 'docs/TRD.md is required by D4');
        $this->trd = file_get_contents($path);
    }

    public function test_has_the_required_sections(): void
    {
        foreach (['Architecture', 'Environments', 'Engineering rules', 'Outbox', 'Performance budget', 'Testing', 'Deployment', 'Spikes', 'Deferred'] as $heading) {
            $this->assertMatchesRegularExpression('/^## .*'.$heading.'/m', $this->trd, "Section '$heading' missing");
        }
    }

    public function test_carries_every_engineering_rule_from_the_plan(): void
    {
        foreach ([
            'app/Actions',
            'lockForUpdate',
            'half-open',
            'brick/money',
            'decimal strings',
            'outbox',
            'afterCommit',
            'Filament tenancy',
            'Sanctum',
            'refresh token',
            'idempotency',
            'request hash',
            'locked sequence row',
            'Cache Everything',
            'Blade',
        ] as $rule) {
            $this->assertStringContainsString($rule, $this->trd, "Rule '$rule' missing");
        }
    }

    public function test_performance_budget_is_unset_not_invented(): void
    {
        $this->assertMatchesRegularExpression('/Performance budget[\s\S]*Unset/', $this->trd);
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(ms|milliseconds?|seconds?|minutes?|hours?|days?|rps|req\/s)\b/i', $this->trd);
    }

    public function test_dependency_versions_are_not_pinned_before_spike_s1(): void
    {
        $this->assertStringContainsString('not pinned', strtolower($this->trd));
        $this->assertStringContainsString('S-1', $this->trd);
        $this->assertDoesNotMatchRegularExpression('/\d+\.\d+\.\d+/', $this->trd, 'No patch-level version may be pinned yet');
    }

    public function test_lists_every_spike(): void
    {
        foreach (range(0, 7) as $n) {
            $this->assertStringContainsString("S-$n", $this->trd);
        }
    }

    public function test_production_access_is_restricted(): void
    {
        $this->assertStringContainsString('Vaiya', $this->trd);
        $this->assertMatchesRegularExpression('/no production credential/i', $this->trd);
    }

    public function test_concurrency_tests_must_use_real_mariadb(): void
    {
        $this->assertMatchesRegularExpression('/real MariaDB/i', $this->trd);
    }

    public function test_deferred_items_name_their_trigger(): void
    {
        foreach (['Flutter', 'Octane', 'SKIP LOCKED', 'WITHOUT OVERLAPS', 'Trigger'] as $item) {
            $this->assertStringContainsString($item, $this->trd);
        }
    }

    public function test_links_to_the_upstream_documents(): void
    {
        foreach (['PRD.md', 'AppFlow.md', 'UI_Spec.md'] as $doc) {
            $this->assertStringContainsString($doc, $this->trd);
        }
    }
}
