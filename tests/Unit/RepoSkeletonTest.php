<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Phase A / A0 contract: the repo + CI skeleton the plan requires.
 * Pure file checks, no framework boot, no database.
 */
class RepoSkeletonTest extends TestCase
{
    private function read(string $relative): string
    {
        $path = dirname(__DIR__, 2).'/'.$relative;
        $this->assertFileExists($path, "$relative is required by A0");

        return file_get_contents($path);
    }

    public function test_docs_index_lists_every_source_of_truth_document(): void
    {
        $index = $this->read('docs/README.md');

        foreach (['PRD.md', 'AppFlow.md', 'UI_Spec.md', 'TRD.md', 'Schema.md', 'Plan.md'] as $doc) {
            $this->assertStringContainsString($doc, $index);
        }
    }

    public function test_claude_md_carries_the_plan_engineering_rules(): void
    {
        $claude = $this->read('CLAUDE.md');

        foreach ([
            'petgroomingbd.com',
            'app/Actions',
            'lockForUpdate',
            'brick/money',
            'outbox',
            'Never touch .env',
            'Unset settings block',
            'Report only checks that were actually run',
        ] as $needle) {
            $this->assertStringContainsString($needle, $claude);
        }
    }

    public function test_claude_md_is_not_the_laravel_boost_bootstrap_stub(): void
    {
        $this->assertStringNotContainsString('laravel/boost', $this->read('CLAUDE.md'));
        $this->assertStringNotContainsString('laravel/boost', $this->read('AGENTS.md'));
    }

    public function test_agents_md_points_to_claude_md(): void
    {
        $this->assertStringContainsString('CLAUDE.md', $this->read('AGENTS.md'));
    }

    public function test_cursorrules_points_to_claude_md(): void
    {
        $this->assertStringContainsString('CLAUDE.md', $this->read('.cursorrules'));
    }

    public function test_line_endings_are_forced_to_lf(): void
    {
        $this->assertMatchesRegularExpression('/^\* text=auto eol=lf$/m', $this->read('.gitattributes'));
        $this->assertMatchesRegularExpression('/^end_of_line = lf$/m', $this->read('.editorconfig'));
    }

    public function test_ci_runs_lint_static_analysis_and_tests_on_php_84(): void
    {
        $ci = $this->read('.github/workflows/ci.yml');

        $this->assertStringContainsString('pull_request', $ci);
        $this->assertStringContainsString("php-version: '8.4'", $ci);
        $this->assertStringContainsString('composer lint', $ci);
        $this->assertStringContainsString('composer analyse', $ci);
        $this->assertStringContainsString('composer test', $ci);
    }

    public function test_composer_exposes_lint_and_analyse_scripts(): void
    {
        $composer = json_decode($this->read('composer.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('lint', $composer['scripts']);
        $this->assertArrayHasKey('analyse', $composer['scripts']);
        $this->assertArrayHasKey('larastan/larastan', $composer['require-dev']);
    }

    public function test_pull_request_template_states_the_definition_of_done(): void
    {
        $template = $this->read('.github/pull_request_template.md');

        foreach (['Task ID', 'composer lint', 'composer analyse', 'composer test', 'docs updated'] as $needle) {
            $this->assertStringContainsString($needle, $template);
        }
    }

    public function test_no_filament_dependency_before_spike_s1(): void
    {
        $composer = $this->read('composer.json');

        $this->assertStringNotContainsString('filament/', $composer, 'Plan: no version pin before spike S-1');
    }
}
