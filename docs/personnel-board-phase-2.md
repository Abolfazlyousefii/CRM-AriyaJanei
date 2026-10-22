# Phase 2 — Personnel organization board

## Included
- GET /admin/users now renders the new dark/RTL organizational dashboard.
- GET /admin/organization/board provides the same dashboard.
- Admin-only GET /admin/organization supplies roles and personnel metadata.
- Existing phase-1 authenticated/CSRF-validated Laravel API handles department
  creation/updates and personnel placement changes.
- Tree/list, search, unit and role filters, account detail, managerial chain,
  optional supervisor and manager fields, and unit editing are enabled.
- Account create/edit forms support organizational positions independently of
  Spatie access roles.
- Existing account block/unblock, activation, archival, and role edit routes
  remain available.
- The 1,293-line obsolete management tree view was removed.
- /admin/personnel stays available as a labelled classic fallback list.

## Safety
- The UI and organization API are protected by auth and role:Admin.
- JS never persists mock user data or credentials to localStorage.
- Real operations are validated by Laravel with cycle, role and unit checks.
- Historical reports and leave approvals are not modified in this phase.

## Host integration
The cPanel live code is still on a separate local Git history. The preserved
integration worktree at ~/CRM-phase1-integration (commit cb28785) includes
the server's 24 local commits, site modifications and phase-1 foundation.
It has NOT been safely published to public GitHub main.

Never reset/clean the live worktree or force-push unpublished server history.
Past local commits could contain private files. Public publication requires
an explicit security audit of ALL commits.

Safe first-stage trial (do not alter the live site):
1. Back up production database and files.
2. Fetch the phase-2 branch into the integration worktree:
   git -C ~/CRM-phase1-integration fetch origin feature/personnel-board-phase-2
3. Test-merge into the integration worktree:
   git -C ~/CRM-phase1-integration merge --no-ff --no-edit origin/feature/personnel-board-phase-2
4. Check worktree status, changed files, route resolution, no secret leakage,
   and run PHP/Laravel tests using an isolated test database.
5. The additive phase-1 migration may still be pending on production. Examine
   migration status and schema and back up before deployment.
6. Only then coordinate controlled deployment to the active ~/CRM worktree.

## Known constraints
Phase 2 covers personnel setup; reports and leave workflows remain as-is
until later phases. The full suite has existing unrelated failures; the focused
organization/personnel tests are the phase-2 check. A production browser
session must still be used to confirm the final UI in its actual environment.
