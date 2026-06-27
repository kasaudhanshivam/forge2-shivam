# Sprint 05: Demo Seeder

**Project:** PulseDesk  
**Sprint Goal:** Populate the database with realistic demo data so judges see a fully populated app on first load.

**Duration:** 1 Sprint  
**Status:** 🔥 ACTIVE

---

## Scope

### Issue #1 — Update UserFactory
**Goal:** Extend the existing `UserFactory` to support `role` and `organization_id`.
**Acceptance Criteria:**
- [ ] `UserFactory` includes `role` field (default: 'customer').
- [ ] `UserFactory` includes `organization_id` field.
- [ ] Add state methods: `admin()`, `agent()`, `customer()` to quickly set role.

### Issue #2 — Create OrganizationFactory
**Goal:** Create a factory for the `Organization` model.
**Acceptance Criteria:**
- [ ] `OrganizationFactory` created at `database/factories/OrganizationFactory.php`.
- [ ] Generates `name` using fake company name.

### Issue #3 — Write DatabaseSeeder
**Goal:** Update `DatabaseSeeder.php` to generate the exact demo dataset.
**Acceptance Criteria:**
- [ ] 1 Organization created.
- [ ] 1 Admin user (role='admin') linked to the org.
- [ ] 2 Agent users (role='agent') linked to the org.
- [ ] 2 Customer users (role='customer') linked to the org.
- [ ] ~12 Tickets created with mixed statuses (open, pending, resolved, closed) and priorities (low, medium, high, urgent).
- [ ] Tickets assigned to agents and requested by customers.
- [ ] Tags populated (e.g., ['bug', 'feature'], ['billing'], ['urgent']).
- [ ] Several threaded comments added to some tickets (mix of public and internal).
- [ ] All data scoped to the same organization_id.

### Issue #4 — Verify Seeder
**Goal:** Run the seeder and confirm data integrity.
**Acceptance Criteria:**
- [ ] `php artisan db:seed --class=DatabaseSeeder` completes without errors.
- [ ] Query counts match expectations (1 org, 5 users, ~12 tickets, several comments).
- [ ] `php artisan test` still passes after seeding.
- [ ] No backend regressions.

---

## Architecture Notes
- **UserFactory** must include `role` and `organization_id` fields. The default Laravel factory only has name, email, password, remember_token.
- **OrganizationFactory** does not exist yet and must be created.
- **TicketFactory** already exists (Sprint 2) and may need updating to support explicit org_id assignment.
- **CommentFactory** already exists (Sprint 2).
- **DatabaseSeeder** should use factories directly or `Model::create()` with explicit attributes.
- Use a fixed password (e.g., `password`) for all demo users so judges can log in easily.

---

## Definition of Done
1. ✅ `OrganizationFactory` created.
2. ✅ `UserFactory` updated with role/org_id + state methods.
3. ✅ `DatabaseSeeder.php` generates 1 org + 5 users + ~12 tickets + threaded comments.
4. ✅ `php artisan db:seed --class=DatabaseSeeder` runs successfully.
5. ✅ `php artisan test` passes (26 passed, 63 assertions).
6. ✅ Code committed to `main` (user pushes manually).

## Deliverable
OpenClaw updated UserFactory, created OrganizationFactory, wrote DatabaseSeeder with demo data (1 org, 1 admin, 2 agents, 2 customers, 12 tickets, threaded comments). Forge verified: `php artisan test` 26 passed, 63 assertions. User pushes manually.

**Status:** ✅ CLOSED — Sprint 5 complete.
