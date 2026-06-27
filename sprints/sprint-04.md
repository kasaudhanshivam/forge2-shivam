# Sprint 04: Dashboard & Metrics

**Project:** PulseDesk  
**Sprint Goal:** Add a multi-tenant metrics API and a polished Dashboard UI to give users an at-a-glance view of their organization's ticket health.

**Duration:** 1 Sprint  
**Status:** ✅ CLOSED

---

## Scope

### Issue #1 — Backend Metrics API
**Goal:** Create a `DashboardController` that returns aggregated ticket metrics scoped to the user's organization.
**Acceptance Criteria:**
- [ ] `DashboardController` created at `app/Http/Controllers/Api/DashboardController.php`.
- [ ] `index()` method queries only tickets where `organization_id = auth()->user()->organization_id`.
- [ ] Returns JSON with:
  - `total_tickets` — count of all tickets in the org
  - `open_tickets` — count where `status = 'open'`
  - `resolved_tickets` — count where `status = 'resolved'`
  - `sla_breached` — mock/hardcoded count (e.g., 2) for now
- [ ] Route `GET /api/dashboard` added to `routes/api.php`, protected by `auth:sanctum`.
- [ ] `BelongsToTenant` trait is respected automatically; no manual scope needed if model already uses it.

### Issue #2 — Frontend Dashboard UI
**Goal:** Create a `Dashboard.jsx` component that fetches and displays the metrics.
**Acceptance Criteria:**
- [ ] `Dashboard.jsx` created at `src/pages/Dashboard.jsx`.
- [ ] On mount, fetches `GET /api/dashboard` via axios.
- [ ] Displays 4 polished Tailwind "Stat Cards":
  - **Total Tickets** — blue theme, ticket icon
  - **Open Tickets** — yellow theme, open icon
  - **Resolved Tickets** — green theme, check icon
  - **SLA Breached** — red theme, alert icon
- [ ] Each card shows the metric number large and a label below.
- [ ] Loading spinner while fetching.
- [ ] Error state handled gracefully.
- [ ] Responsive grid: 1 column mobile, 2 columns tablet, 4 columns desktop.

### Issue #3 — Routing & Navigation
**Goal:** Wire the Dashboard into the app shell.
**Acceptance Criteria:**
- [ ] Route `/dashboard` added to `App.jsx` as a protected route.
- [ ] Default route `/` redirects to `/dashboard` when authenticated (instead of `/tickets`).
- [ ] Header nav bar updated with links: "Dashboard" and "Tickets" (visible when authenticated).
- [ ] Active link highlighted.

### Issue #4 — Verification
**Goal:** Ensure everything works end-to-end.
**Acceptance Criteria:**
- [ ] `php artisan test` still passes (all existing + new tests).
- [ ] `npm run build` still passes (frontend compiles cleanly).
- [ ] No backend regressions.

---

## Architecture Notes
- **Backend:** All metrics queries must respect `BelongsToTenant` global scope. The `Ticket` model already has this trait; the controller should just use `Ticket::where(...)` naturally.
- **Frontend:** Reuse existing axios setup (AuthContext already configures baseURL and headers).
- **SLA Breached:** Hardcode to `2` for now. Future sprint will compute actual SLA violations.

---

## Definition of Done
1. ✅ `GET /api/dashboard` returns org-scoped metrics (total, open, resolved, SLA breached).
2. ✅ `Dashboard.jsx` displays 4 polished stat cards with Tailwind.
3. ✅ React Router handles `/dashboard` as default protected route.
4. ✅ Header nav includes Dashboard and Tickets links.
5. ✅ `php artisan test` passes (all existing tests).
6. ✅ `npm run build` passes (frontend compiles cleanly).
7. ✅ Code committed to `main` (user pushes manually).

## Deliverable
OpenClaw implemented DashboardController, Dashboard.jsx, App.jsx routing, and navigation. Forge verified: `php artisan test` 26 passed (63 assertions), `npm run build` zero errors. User pushes manually.

**Status:** ✅ CLOSED — Sprint 4 complete.
