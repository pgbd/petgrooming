<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Spike S-1 contract: the planned stack installs without conflict, versions
 * are pinned by the lockfile, and the result is written down faithfully.
 */
class StackSpikeTest extends TestCase
{
    private const PACKAGES = [
        'filament/filament',
        'livewire/livewire',
        'bezhansalleh/filament-shield',
        'spatie/laravel-activitylog',
        'spatie/laravel-sitemap',
        'laravel/sanctum',
        'dedoc/scramble',
        'brick/money',
    ];

    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    /** @return array<string, string> package name => locked version */
    private function locked(): array
    {
        $lock = json_decode(file_get_contents($this->root.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
        $versions = [];

        foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
            $versions[$package['name']] = $package['version'];
        }

        return $versions;
    }

    public function test_every_planned_package_is_locked(): void
    {
        $locked = $this->locked();

        foreach (self::PACKAGES as $package) {
            $this->assertArrayHasKey($package, $locked, "$package is not in composer.lock");
        }
    }

    public function test_direct_requirements_are_declared_and_stable(): void
    {
        $composer = json_decode(file_get_contents($this->root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach (array_diff(self::PACKAGES, ['livewire/livewire']) as $package) {
            $this->assertArrayHasKey($package, $composer['require'], "$package must be a direct requirement");
            $this->assertStringNotContainsString('dev-', $composer['require'][$package], "$package must not track a dev branch");
        }

        $this->assertNotSame('dev', $composer['minimum-stability'] ?? 'stable', 'Unstable packages are not allowed');
    }

    public function test_the_s1_record_matches_the_lockfile(): void
    {
        $path = $this->root.'/docs/spikes/S-1.md';
        $this->assertFileExists($path, 'The S-1 result must be written down');
        $doc = file_get_contents($path);
        $locked = $this->locked();

        foreach (self::PACKAGES as $package) {
            $this->assertStringContainsString($package, $doc);
            $this->assertStringContainsString($locked[$package], $doc, "S-1.md must state the locked version of $package");
        }
    }

    public function test_the_s1_record_states_what_was_not_verified(): void
    {
        $doc = file_get_contents($this->root.'/docs/spikes/S-1.md');

        $this->assertStringContainsString('MariaDB', $doc);
        $this->assertStringContainsString('VPS', $doc);
        $this->assertMatchesRegularExpression('/not verified|unverified/i', $doc);
    }
}
