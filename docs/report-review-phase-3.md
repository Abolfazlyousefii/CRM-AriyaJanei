# CRM Report Review — Phase 3 guarded pilot

## Safety posture

This change is opt-in only. It DOES NOT change the legacy report submission,
report status enum, historical reports, leave requests, or staff role mappings
when the defaults below are preserved.

Configuration is in config/report_review.php. The production-safe defaults are:

    REPORT_REVIEW_WORKFLOW_ENABLED=false
    REPORT_REVIEW_PILOT_USER_IDS=
    REPORT_REVIEW_ALL_USERS=false
    REPORT_REVIEW_MAX_STEPS=3

Even if enabled is set to true, the workflow will process **no new reports**
until an explicit pilot author allowlist is configured OR an administrator
intentionally sets REPORT_REVIEW_ALL_USERS=true. Never enable the all-users
mode until the entire organizational chart, manager chain and permission audit
have been verified. Do not edit the environment on behalf of users.

The existing ReportController creates and notifies as before if:
- the flag is off,
- the author is not in the pilot allowlist,
- no valid manager chain is configured,
- or the imported hierarchy still has a cycle.

With a valid opted-in chain, only the immediate assigned reviewer receives
the new-report notification. Subsequent reviewer notifications are emitted
after previous steps approve. Report status remains submitted/read for backward
compatibility, while new approval state lives in separate tables.

## Schema

One workflow per submitted report, 1-5 immutable sequence assignments
(snapshot at submission), a retained per-step reviewer name, a current
reviewer/status, and a chronological append-only review_events table.

Migration: database/migrations/2026_10_22_100001_create_report_review_workflows.php

This is an additive migration: it never edits existing reports or their
attachments, ratings or feedback.

A returned report must have its description changed by the author before
resubmission. Pending or approved pilot reports cannot be edited/deleted via
the legacy editor. Legacy reports remain editable under original rules.

## Pilot reviewer interface

The endpoint GET /report-review/inbox displays reports assigned to the
authenticated reviewer and the author's own reports returned for revision.
Approve / return / resubmit POST endpoints validate the server-side assigned
reviewer or author and the current workflow state transactionally.

An Admin cannot implicitly approve an unassigned workflow stage. Higher
management sees only stages actually assigned to them in this pilot inbox.

## Tests

Focused MySQL CI includes the existing personnel/hierarchy/report-visibility
tests and the new ReportReviewWorkflowPhaseThreeTest. The existing full suite
has unrelated baseline failures in authentication, profile and customer
satisfaction and is informational only. Syntax checks include PHP and JS.

## Deployment (NOT YET on production)

The production cPanel worktree at ~/CRM uses an integration history that
is not fully synchronized with public GitHub. The phase-2 branch is not yet
merged into public main; this phase-3 PR targets the phase-2 branch.

Before integration, audit any private server-only commit history for secrets.
Never force push, reset or clean the active server project. First perform a
local non-production worktree merge with the phase-3 GitHub feature branch,
inspect diffs, then run tests against a separate MySQL database.

After approved backup, code deployment and migration, retain the flag OFF.
Configure specific organizational assignments and validate a *single pilot
author* before considering activation. Preserve a rollback plan that disables
the config flag without reverting database schema.

DO NOT deploy this branch by directly pulling it into ~/CRM.
DO NOT set REPORT_REVIEW_ALL_USERS=true until after a complete security
review of legacy report show/edit/feedback endpoints and organization mapping.
