# ADR-0002: Functions are roles, not accounts

Status: Accepted
Resolves contradiction C2 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

The owner confirmed 2 admins and 4 staff. The user journeys name receptionist, groomer, cashier and manager, which are four functions, not four accounts.

## Decision

The six accounts are individual and unchanged. Receptionist, cashier, groomer and manager are roles that any account can hold, several at once. Admins hold all roles. The owner decides which person holds which roles.

## Consequences

Authorization is by role and branch, tested by the permission matrix in M1. The assignment of people to roles stays Unset in the PRD until the owner provides it. No shared logins.
