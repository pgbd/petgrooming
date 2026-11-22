## Task ID

<module>-<n>

## Goal

One sentence.

## Definition of done

- [ ] Failing test written first, and watched failing
- [ ] `composer lint` passes
- [ ] `composer analyse` passes
- [ ] `composer test` passes (full suite)
- [ ] Diff reviewed; docs updated where behaviour changed
- [ ] No unreported TODOs, no new dependency without approval

## Report

Exact commands run, actual output, gaps, open questions for the owner.

High-risk paths (`app/Actions/Billing/**`, `app/Actions/Scheduling/**`, `app/Policies/**`, migrations, ledger) need Vaiya's review.
