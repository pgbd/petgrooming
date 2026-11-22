# ADR-0001: Local runtime stays Laragon

Status: Accepted
Resolves contradiction C1 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

The confirmed baseline names Laragon on Windows at D:\laragon\www\petgroomingbd. Earlier general advice proposed WSL2 with Docker Compose. Laragon currently provides PHP 8.4 and MariaDB 10.11, but the plan targets MariaDB 11.4.

## Decision

Laragon is the local runtime. A MariaDB 11.4 instance is added next to it for concurrency tests. WSL2 and Docker Compose remain optional and are not required by any task.

## Consequences

Local setup is documented against Laragon. Concurrency tests run on MariaDB 11.4, locally or on the VPS dev server. S-1 verifies PHP and MariaDB versions on both machines.
