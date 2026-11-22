<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * S-1 and S-2 were re-run on the VPS dev server. The records must say what was
 * verified there and what was not, and the Docker setup must be reproducible
 * without committing a secret.
 */
class VpsVerificationRecordTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    private function read(string $relative): string
    {
        $path = $this->root.'/'.$relative;
        $this->assertFileExists($path, "$relative is required");

        return file_get_contents($path);
    }

    public function test_s1_records_the_vps_result_with_versions(): void
    {
        $doc = $this->read('docs/spikes/S-1.md');

        $this->assertStringContainsString('## VPS verification', $doc);
        $this->assertStringContainsString('8.4.28', $doc);
        $this->assertStringContainsString('11.4.13', $doc);
        $this->assertStringContainsString('composer install', $doc);
    }

    public function test_s2_records_the_vps_result_with_versions(): void
    {
        $doc = $this->read('docs/spikes/S-2.md');

        $this->assertStringContainsString('## VPS verification', $doc);
        $this->assertStringContainsString('11.4.13', $doc);
        $this->assertStringContainsString('three', strtolower($doc));
    }

    public function test_records_keep_stating_what_is_still_unverified(): void
    {
        foreach (['docs/spikes/S-1.md', 'docs/spikes/S-2.md'] as $file) {
            $this->assertMatchesRegularExpression('/not verified/i', $this->read($file), "$file must keep its limits");
        }

        $this->assertStringContainsString('HTTP', $this->read('docs/spikes/S-1.md'));
    }

    public function test_the_docker_setup_is_committed_without_a_secret(): void
    {
        $compose = $this->read('infra/vps-dev/compose.yml');
        $dockerfile = $this->read('infra/vps-dev/Dockerfile');

        $this->assertStringContainsString('image: mariadb:11.4', $compose);
        $this->assertStringContainsString('${PGDEV_DB_ROOT_PASSWORD}', $compose);
        $this->assertStringContainsString('FROM php:8.4-cli', $dockerfile);
        $this->assertStringContainsString('mem_limit', $compose);
        $this->assertDoesNotMatchRegularExpression('/PASSWORD:\s*[A-Za-z0-9]{8,}/', $compose, 'No literal password in compose.yml');
        $this->assertStringNotContainsString('ports:', $compose, 'No port may be published to the host');
    }

    public function test_the_infra_readme_explains_layout_and_secret_handling(): void
    {
        $readme = $this->read('infra/vps-dev/README.md');

        foreach (['petgroomdev-infra', '.env', 'not committed', 'APP_KEY', 'tests/Concurrency'] as $needle) {
            $this->assertStringContainsString($needle, $readme, "README lacks '$needle'");
        }
    }
}
