# Pet Grooming BD — Technical Requirements (D4)

Status: **Draft for G0 review.** Built from the development plan (sections 2, 3, 4, 8, 9, 10) and from [PRD.md](PRD.md), [AppFlow.md](AppFlow.md) and [UI_Spec.md](UI_Spec.md). The plan states that performance figures are targets, not proven capacity, and that dependency versions are not pinned until spike S-1 proves compatibility. This document follows both.

## Architecture

- Laravel modular monolith on PHP 8.4, MariaDB (11.4 LTS preferred), Redis for cache and queues.
- Public site: Blade server-side rendering. Staff back office: Filament 5 with Livewire 4. Versioned REST API at `/api/v1` for future clients (Flutter comes later as a separate API client).
- Business rules live only in `app/Actions`. Blade views, Filament pages and API controllers call the same Action classes, and shared policies enforce authorization.
- Planned packages (compatibility unproven until S-1): Filament Shield, activity log, sitemap, Sanctum, Scramble for OpenAPI 3.1, brick/money, mPDF for Bengali PDFs.
- Migrations arrive milestone by milestone, never all at once. Portal tables such as customer accounts are not built in the MVP.

## Environments and branching

| Place | Who works | Rule |
|---|---|---|
| Local (Laragon, Windows) | Vaiya with Claude Code | `dev/*` branch, then PR |
| VPS dev | Hermes with Claude worker | `agent/<task-id>` branch; nobody pushes to `main` |
| `main` | Protected | PR, green CI and a reviewer pass; high-risk paths also get Vaiya's review |
| Dev deploy | Hermes, after merge | Only `test.dev` and `admin.dev` |
| Production deploy | Vaiya personally | Versioned image; Hermes holds no production credential |

- Local and VPS must share the PHP 8.4 and MariaDB major version and the same Bengali font. Concurrency tests run in both places, otherwise results mislead. Local MariaDB is currently older than the target and must be aligned (checked in S-1).
- Line endings are forced to LF through `.gitattributes` and `.editorconfig`.
- Hermes takes instructions only from Vaiya's Discord ID. Secrets, `.env` files and tokens never go through Discord. `rm -rf`, force push and database drops need explicit approval.
- Dev and production may share one VPS. Recommended: separate Linux users, Docker networks and resource limits, with room to move production to its own server later. The capacity assumption for the VPS is from the plan's earlier advice and needs confirming.

## Engineering rules

These are binding and mirrored in `CLAUDE.md`.

1. Business rules live only in `app/Actions`.
2. Booking confirm runs in a transaction with `lockForUpdate()` on one concrete parent row (a resource or a resource-day), uses half-open `[start, end)` overlap and a bounded retry count. Locking an empty range does not block other sessions.
3. `WITHOUT OVERLAPS` is only a second guard, after a two-session test passes. It is not relied on while upstream support is unresolved.
4. Money is DECIMAL in the database or integer minor units with brick/money, decimal strings in JSON, and never floats, including in Dart.
5. `afterCommit` jobs stop reads of uncommitted data but do not guarantee delivery. A critical SMS writes an outbox row in the same transaction.
6. Filament tenancy is not a security boundary. Branch isolation is a global scope, policies and API-route tests; API routes sit outside Filament.
7. Sanctum has no refresh token: tokens expire and native clients log in again with an OTP. No refresh flow is built.
8. Retries are made safe by idempotency: a durable table with a unique key, a request hash and the stored response. The same key with a different payload returns 422. Replays re-check authorization.
9. Invoice numbers are not AUTO_INCREMENT. They come from a locked sequence row at issue time; the Mushak format waits for the accountant.
10. HTML caching: Cloudflare "Cache Everything" is never used on Laravel HTML. Admin, login, booking and API bypass the cache, verified with `curl -I` and a two-session test.
11. FAQ rich results were switched off in May 2026. FAQPage markup is optional and never counted in traffic estimates. No review markup on the site's own pages.
12. Financial records are never deleted. Corrections are linked documents.

## Outbox and notifications

- A business change and its outbox row commit together. A relay sends SMS, retries failures and handles stale reminders.
- A provider failure never rolls back or changes a booking; the failure is visible to staff.
- A full relay with `SKIP LOCKED` is deferred until volume or missed reminders justify it (see Deferred).

## Performance budget

All figures are **Unset**. The plan calls performance numbers targets, not proven capacity, and requires measuring on the real VPS. The load profile and traffic and booking volume estimates are an open owner decision. Gate 11 requires performance measured against the agreed profile, with a baseline taken in M0.

## Testing and CI

- Layers: unit, feature and integration, concurrency, browser, visual and manual, security, performance and recovery.
- Critical database tests (double booking, duplicate payment, numbering) run on real MariaDB. SQLite cannot prove MariaDB locking.
- Payment and SMS tests use fakes or provider sandboxes, never real collection or real customer messages.
- Every PR runs Pint, Larastan and the full test suite, plus the frontend build, API contract validation, dependency security checks and browser smoke tests once those exist.
- A broken check is never bypassed by deleting a test, adding broad suppression or disabling a permission.
- An implementer writes the failing test first and reports only checks actually run. A separate test agent writes acceptance tests from the spec, and a separate reviewer checks the diff against the definition of done.

## Deployment

- Production runs Docker Compose on the VPS, with Redis and Horizon assumed. Releases come from a specific reviewed commit or image.
- Agents have no production credential or shell. Vaiya deploys production personally.
- Backup and restore are proven in S-7. RPO and RTO are **Unset** and need the owner's and Vaiya's signature at the end of M0.
- Dev URLs sit behind a password or Cloudflare Access, are `noindex`, and hold no real customer data.

## Spikes (M0)

| Spike | Proves |
|---|---|
| S-0 Hermes handoff | Discord to Hermes to a Claude worker runs one small task end to end |
| S-1 Stack | Planned packages install cleanly with no conflicts; versions are decided here, then pinned; MariaDB versions checked on both machines |
| S-2 Concurrency | Two parallel confirms on one resource: exactly one succeeds, the other gets a clean conflict, deadlock retry works |
| S-3 WITHOUT OVERLAPS | Behaviour recorded on a scratch table; never the only guard |
| S-4 Bengali PDF | Real invoice with shaping, pagination and a readable QR on the target printer |
| S-5 Cache and CSRF | No cookie leakage with a response cache; bypass plan written |
| S-6 API slice | `GET /api/v1/branches` and an idempotent `POST /api/v1/booking-requests`; the same Action serves Filament; contract checked in CI |
| S-7 Restore drill | Database, uploads and keys restored from backup; restore time measured |

## Deferred

| Item | Trigger |
|---|---|
| Flutter app | Push or loyalty is needed, or the PWA falls behind on measured numbers |
| Full outbox relay with `SKIP LOCKED` | SMS volume grows or a reminder is missed |
| `WITHOUT OVERLAPS` as a second guard | S-3 passes, or a double booking occurs |
| Octane | Measured TTFB fails and caching does not fix it |
| Filament multi-tenancy | Separate businesses on one install |
| NBR e-invoicing and EFD | The accountant's written advice or an NBR notice |
| Binlog point-in-time recovery | Losing a day of income becomes unacceptable |
| Area-based landing pages | Only one per real branch |
| Second theme | After the first theme is stable, with an HTML diff per theme |

## Open items

- Dependency versions: decided in S-1, then pinned in the lockfile.
- Performance budget, load profile and RPO/RTO: owner and Vaiya.
- Hermes handoff mechanics, permissions, cost and rate limits: S-0.
