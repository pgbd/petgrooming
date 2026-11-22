# ADR-0005: develop is the integration branch

Status: Accepted
Resolves contradiction C5 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

The plan's branch model names dev, agent and main branches. The repository also has a develop branch, created at the owner's request.

## Decision

develop is the integration branch. Branches named dev/* (local) and agent/<task-id> (VPS) open PRs into develop. main only receives release PRs from develop and stays protected: PR, green CI and a reviewer.

## Consequences

TRD documents the model. Dev deploys follow merges into develop. A stacked PR must be retargeted before its base branch is deleted.
