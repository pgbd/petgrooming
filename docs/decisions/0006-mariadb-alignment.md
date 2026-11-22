# ADR-0006: Align MariaDB to 11.4 before S-2

Status: Accepted
Resolves contradiction C6 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

Local MariaDB is 10.11 while the plan prefers 11.4 LTS. Concurrency and locking behaviour must be proven on the same major version as production.

## Decision

Install MariaDB 11.4 locally before spike S-2, or run S-2 on the VPS dev server on 11.4. S-1 still records the versions on both machines.

## Consequences

No concurrency result is accepted from MariaDB 10.11. Gate 1 evidence names the version it ran on.
