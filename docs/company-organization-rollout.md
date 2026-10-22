# Company organization rollout

## Baseline

The application source is carried forward from integrated server commit
`7fffaf542f5a9e2fb9b7ec75b735376596bf5a29`, including company field visits,
leave approvals, personnel board and the opt-in report review engine.
Only reviewed application changes are transferred onto public main; private
server ancestry, environment files and hosting overrides are not imported.
The existing local working tree is preserved separately.

Composer resolves against PHP 8.2 so a dependency update cannot silently
require a newer PHP runtime than the application supports.

## Delivery phases

1. Reconcile company source and public main, verify dependency installation
   and run organization, report, leave and field visit regression tests.
2. Complete personnel placement tools and diagnostics using the approved board.
   Administrators explicitly choose departments, positions and reporting managers.
   Do not infer the real organization from names or access roles.
3. Replace legacy report exceptions and broad controller access with scoped
   authorization, including separate checks for viewing, editing, deleting,
   feedback and rating. Organizational positions do not grant global access.
4. Complete reviewer inbox, revision history and authorized reassignment.
   Let administrators configure module-specific review stages and their order;
   keep this configuration separate from software roles and person names.
   Submission snapshots must survive personnel moves and feature flag changes.
5. Connect new leave requests to the configured responsible person while
   retaining historical approvals, substitutes and accounting visibility.
6. Deploy verified commits, apply only understood additive migrations and test
   a synthetic employee chain before expanding rollout.

## Activation gates

Report review stays disabled until an administrator configures a valid chain.
Never repair imported reporting cycles by guessing real organizational links.
Never run unrelated pending migrations as part of the report rollout.
Changes are committed by phase and published to main without force pushing.
No production data reset, automatic historical report conversion or role removal.
