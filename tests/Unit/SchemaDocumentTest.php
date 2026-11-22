<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * D5 contract: docs/Schema.md lists entities with their milestone and the
 * constraints the plan states, and builds nothing the plan defers.
 */
class SchemaDocumentTest extends TestCase
{
    private const ENTITIES = [
        'branches', 'users', 'customers', 'customer_contacts', 'pets',
        'services', 'price_rules', 'settings', 'setting_versions', 'reasons',
        'resources', 'booking_requests', 'appointments', 'appointment_events',
        'invoices', 'invoice_lines', 'invoice_sequences', 'payments',
        'advance_entries', 'refund_invoices', 'daily_closes',
        'outbox_messages', 'idempotency_keys',
    ];

    private string $schema;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 2).'/docs/Schema.md';
        $this->assertFileExists($path, 'docs/Schema.md is required by D5');
        $this->schema = file_get_contents($path);
    }

    private function row(string $entity): string
    {
        preg_match('/^\| `'.preg_quote($entity, '/').'` \|.*$/m', $this->schema, $m);
        $this->assertNotEmpty($m, "Entity '$entity' has no table row");

        return $m[0];
    }

    public function test_every_entity_has_a_row_with_a_milestone(): void
    {
        foreach (self::ENTITIES as $entity) {
            $this->assertMatchesRegularExpression('/\| M[1-5] \|/', $this->row($entity), "$entity needs a milestone M1-M5");
        }
    }

    public function test_milestones_follow_the_plan(): void
    {
        $this->assertStringContainsString('| M1 |', $this->row('customers'));
        $this->assertStringContainsString('| M2 |', $this->row('appointments'));
        $this->assertStringContainsString('| M3 |', $this->row('refund_invoices'));
        $this->assertStringContainsString('| M5 |', $this->row('outbox_messages'));
    }

    public function test_ledger_and_snapshots_are_append_only_or_immutable(): void
    {
        $this->assertStringContainsString('append-only', $this->row('advance_entries'));
        $this->assertStringContainsString('immutable', $this->row('invoice_lines'));
    }

    public function test_money_uses_decimal_and_never_float(): void
    {
        $this->assertStringContainsString('DECIMAL(12,2)', $this->schema);
        $this->assertDoesNotMatchRegularExpression('/\bfloat\b|\bdouble\b/i', preg_replace('/never float|no float/i', '', $this->schema));
    }

    public function test_idempotency_and_numbering_constraints_are_recorded(): void
    {
        $idempotency = $this->row('idempotency_keys');
        $this->assertStringContainsString('unique', strtolower($idempotency));
        $this->assertStringContainsString('request hash', $idempotency);
        $this->assertStringContainsString('stored response', $idempotency);

        $this->assertStringContainsString('locked', $this->row('invoice_sequences'));
        $this->assertStringContainsString('not AUTO_INCREMENT', $this->schema);
    }

    public function test_booking_tables_support_locking_and_versioning(): void
    {
        $this->assertStringContainsString('version', $this->row('appointments'));
        $this->assertStringContainsString('half-open', $this->schema);
        $this->assertStringContainsString('resource-day', $this->schema);
    }

    public function test_refund_invoice_stores_reason_id_and_label_snapshot(): void
    {
        $refund = $this->row('refund_invoices');
        $this->assertStringContainsString('reason id', $refund);
        $this->assertStringContainsString('label snapshot', $refund);
    }

    public function test_portal_tables_are_not_built_in_the_mvp(): void
    {
        $this->assertStringContainsString('customer_accounts', $this->schema);
        $this->assertDoesNotMatchRegularExpression('/^\| `customer_accounts` \|/m', $this->schema);
    }

    public function test_includes_an_erd_and_marks_open_columns(): void
    {
        $this->assertStringContainsString('erDiagram', $this->schema);
        $this->assertStringContainsString('Unset', $this->schema);
    }

    public function test_links_to_the_upstream_documents(): void
    {
        foreach (['PRD.md', 'AppFlow.md', 'TRD.md'] as $doc) {
            $this->assertStringContainsString($doc, $this->schema);
        }
    }
}
