# Organizational hierarchy — phase 1

This phase adds a backend foundation only. It does not change the report
approval workflow, leave workflow, or the existing user administration UI.

## Data model

- users.manager_id: explicit direct manager, unchanged from the existing CRM.
- users.department_id: department membership, unchanged.
- users.org_position: optional organizational position, separate from Spatie
  permission roles. Supported values: staff, supervisor,
  department_manager, internal_manager, executive_manager, ceo.
- departments.supervisor_id: optional unit supervisor.
- departments.manager_id: optional unit manager.
- departments.parent_manager_id: optional manager above the unit.
  All three leaders are optional.

Routing rule: if users.manager_id is set, it wins. Otherwise the service
derives the nearest active leader. Staff route to supervisor > unit manager >
parent; supervisor routes to unit manager > parent; unit manager routes to
parent. Senior management uses explicit manager_id.

Changing a department leader does not silently rewrite users.manager_id.
The phase-2 UI will clarify explicit links versus derived fallbacks.

## Admin-only JSON endpoints (web middleware; CSRF protected)

- GET /admin/organization
- POST /admin/organization/departments
- PATCH /admin/organization/departments/{department}
- PATCH /admin/organization/personnel/{user}

Use Accept: application/json and a valid Laravel session + CSRF token.
An explicit manager_id: null switches an employee to department fallback.
An omitted manager_id preserves the existing explicit assignment.

Cycles and repeated leaders are rejected, with transactional rollback.

## Deployment

1. Verify that the production users and departments schemas match the
   repository and take a database backup.
2. Deploy code and run: php artisan migrate --force
3. Run tests and validate hierarchy endpoints as Admin.
4. Configure departments and personnel via the phase-2 interface.

Do not run migrate:fresh or wipe previous migration history in production.
The migration only adds nullable columns and never deletes existing reports.

Phase 2 replaces the UI. Phase 3 uses this service for report review with
snapshot workflow stages and targeted notifications. Existing reports and
leave approval workflows remain unchanged in phase 1.
