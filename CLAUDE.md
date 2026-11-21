# Pet Grooming BD

Stack: Laravel 13, PHP 8.4, Filament 5, Livewire 4, MariaDB 11.4, Redis.
Source of truth: docs/PRD.md, AppFlow.md, UI_Spec.md, TRD.md, Schema.md, Plan.md.
Canonical domain: petgroomingbd.com. Dev: test.dev.petgrooming.bd, admin.dev.petgrooming.bd.

## Rules

- Business rules live only in app/Actions; Blade, Filament and API call them.
- Booking confirm: transaction + lockForUpdate on a concrete parent row, half-open [start, end), attempts 3-5.
- Money: DECIMAL in DB, decimal strings in JSON, brick/money. No floats.
- Critical SMS: write an outbox row in the same transaction. afterCommit alone is not delivery.
- Branch isolation: global scope + policies + API tests. Filament tenancy is not a security boundary.
- Cross-branch customer history is read-only unless a write permission exists.
- Sanctum has no refresh tokens: expiring tokens, re-login by OTP.
- Unset settings block the operation visibly. Never invent fees, penalties or policies.
- Financial records are never deleted; corrections are linked documents.
- Never touch .env, production data or applied migrations; add new migrations only.
- Ask the owner before anything outside the agreed scope.
- Report only checks that were actually run, with their output.

## Workflow

- Test first: write a failing test, watch it fail, then write the minimum code.
- Branches: `agent/<task-id>` (VPS) or `dev/*` (local). Never push to `main`; PR + green CI + reviewer.
- Checks before any PR: `composer lint`, `composer analyse`, `composer test`.
- No dependency may be added or version-pinned without approval (Filament and other packages wait for spike S-1).
- Never put secrets, `.env` contents or tokens in chat, commits or docs.
