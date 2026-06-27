# Sprint 03: Frontend Wiring (React 19 + Vite + Tailwind)

**Project:** PulseDesk  
**Sprint Goal:** Scaffold and wire a React 19 frontend with Vite and Tailwind CSS. Implement authentication flow (Axios + Sanctum), ticket board with filtering, and threaded ticket detail views.

**Duration:** 1 Sprint  
**Status:** 🔥 ACTIVE

---

## Scope

### Issue #1 — Scaffold Frontend (React 19 + Vite + Tailwind)
**Goal:** Initialize the frontend workspace inside `~/forge2-shivam/frontend/`.
**Acceptance Criteria:**
- [ ] React 19 project scaffolded with Vite (React + TypeScript template).
- [ ] Tailwind CSS configured with PostCSS.
- [ ] Axios installed.
- [ ] React Router v7 installed.
- [ ] Environment variable `VITE_API_URL` points to Laravel backend.
- [ ] `.gitignore` excludes `node_modules` and `.env`.

### Issue #2 — Auth Integration (Axios + Sanctum)
**Goal:** Implement login/logout with Laravel Sanctum token authentication.
**Acceptance Criteria:**
- [ ] Axios instance with `baseURL` = `VITE_API_URL` and `Content-Type: application/json`.
- [ ] `AuthContext` (React Context) stores user, token, and role.
- [ ] `login(email, password)` → POST `/api/login`, stores token in `localStorage`, sets Axios default header `Authorization: Bearer <token>`.
- [ ] `register(name, email, password, organization_name)` → POST `/api/register`, stores token, sets user.
- [ ] `logout()` → POST `/api/logout`, clears token and user state, navigates to `/login`.
- [ ] Axios request interceptor attaches `Authorization` header from `localStorage`.
- [ ] Axios response interceptor catches 401 → clears auth and redirects to `/login`.

### Issue #3 — Ticket Board View
**Goal:** Dashboard component that lists tickets from `/api/tickets` with filtering and search.
**Acceptance Criteria:**
- [ ] `TicketBoard` component fetches `/api/tickets` on mount.
- [ ] Displays tickets in a table/card grid: subject, status, priority, assignee name, requester name, created_at.
- [ ] Filter UI: dropdowns for `status` (open, pending, resolved, closed) and `priority` (low, medium, high, urgent).
- [ ] Search input filters by subject/description text (passed as `?search=`).
- [ ] Uses `TicketResource` / `TicketCollection` JSON structure (`data[]`).
- [ ] Clicking a ticket navigates to `/tickets/:id`.
- [ ] Loading and empty states handled.

### Issue #4 — Ticket Detail View
**Goal:** Single-ticket page with threaded conversation and comment form.
**Acceptance Criteria:**
- [ ] `TicketDetail` component fetches `/api/tickets/:id` and `/api/tickets/:id/comments`.
- [ ] Displays ticket details: subject, description, status badge, priority badge, requester, assignee, tags, created_at.
- [ ] Lists comments in chronological order.
- [ ] Comment card shows: user name, body, timestamp.
- [ ] Internal comments (when visible) styled differently (e.g., yellow background).
- [ ] Customers do not see internal comments (API already filters them).
- [ ] Comment form: textarea + submit button → POST `/api/tickets/:id/comments`.
- [ ] If user role is `agent` or `admin`, show an "Internal note" checkbox that sets `is_internal=true`.
- [ ] After successful comment creation, re-fetch comments list.
- [ ] Back button navigates to `/tickets`.

### Issue #5 — Routing
**Goal:** Wire up React Router for SPA navigation.
**Acceptance Criteria:**
- [ ] Route `/login` → `LoginPage` (public).
- [ ] Route `/register` → `RegisterPage` (public).
- [ ] Route `/tickets` → `TicketBoard` (protected, requires auth).
- [ ] Route `/tickets/:id` → `TicketDetail` (protected, requires auth).
- [ ] Protected route wrapper checks auth context; redirects to `/login` if missing.
- [ ] Default route (`/`) redirects to `/tickets` if authenticated, else `/login`.

### Issue #6 — UI Polish & Tailwind
**Goal:** Consistent, clean UI using Tailwind CSS.
**Acceptance Criteria:**
- [ ] Layout shell with header (app name + logout button) and main content area.
- [ ] Status badges color-coded: open (blue), pending (yellow), resolved (green), closed (gray).
- [ ] Priority badges color-coded: low (gray), medium (blue), high (orange), urgent (red).
- [ ] Responsive layout (mobile stack, desktop table/grid).
- [ ] No inline styles — all via Tailwind utility classes.

---

## Architecture Notes
- **Directory:** All frontend code lives inside `~/forge2-shivam/frontend/`.
- **Tech Stack:** React 19, Vite 6+, TypeScript (or JavaScript — your call), Tailwind CSS 4, React Router 7, Axios.
- **API Base URL:** Read from `import.meta.env.VITE_API_URL`.
- **Auth Token:** Stored in `localStorage` under key `pulse_token`.
- **No backend changes** — consume existing Laravel API only.

---

## Definition of Done
1. ✅ Vite + React 19 + Tailwind scaffolded inside `~/forge2-shivam/frontend/`.
2. ✅ Auth context manages login, register, logout, token persistence, Axios interceptors.
3. ✅ Ticket Board lists tickets with status/priority/search filtering.
4. ✅ Ticket Detail shows ticket info, threaded comments, and comment form with internal-note toggle for agents/admins.
5. ✅ React Router handles `/login`, `/register`, `/tickets`, `/tickets/:id` with protected routes.
6. ✅ UI polished with Tailwind (badges, responsive, no inline styles).
7. ✅ `npm run build` completes with zero errors.
8. ✅ Code committed to `main` (user pushes manually).

## Deliverable
Sprint 3 implementation is **COMPLETE**. OpenClaw scaffolded Vite + React 19 + Tailwind and built LoginPage, RegisterPage, AuthContext, and ProtectedRoute. Forge created App.jsx (router shell), TicketBoard.jsx (table with filtering/search), TicketDetail.jsx (threaded comments + internal note toggle), wired Axios 401 interceptor, and verified `npm run build`. User will push manually.

**Status:** ✅ CLOSED — Ready for Sprint 4.
