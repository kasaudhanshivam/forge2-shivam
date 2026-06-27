# Sprint 02: Tickets Core CRUD & Threaded Replies

**Project:** PulseDesk  
**Sprint Goal:** Implement full Ticket lifecycle (CRUD + filtering) and threaded Comment system with role-based visibility, all scoped to the authenticated user's organization.  
**Tech Stack:** Laravel 11 + MySQL + Sanctum + PHPUnit  
**Working Directory:** `~/forge2-shivam/backend`

---

## Scoped Issues

### Issue #1 — Update Ticket Model & Migration
- Add `tags` (json, nullable) to existing `Ticket` model & migration
- Existing fields already present: `subject`, `description`, `status`, `priority`, `requester_id`, `assignee_id`, `organization_id`
- Enum/string constraints: `status` ∈ {`open`, `pending`, `resolved`, `closed`}, `priority` ∈ {`low`, `medium`, `high`, `urgent`}
- Cast `tags` as `array`
- Relationships: `requester()` → `User`, `assignee()` → `User` (nullable), `comments()` → `Comment`, `organization()` → `Organization`

### Issue #2 — Create Comment Model & Migration
- Model: `Comment` with `BelongsToTenant` trait
- Migration: `comments` table with:
  - `id`, `ticket_id` (foreign key → `tickets` cascade), `user_id` (foreign key → `users` cascade)
  - `body` (text), `is_internal` (boolean, default `false`)
  - `organization_id` (foreign key → `organizations` cascade)
  - timestamps
- Relationships: `ticket()` → `Ticket`, `user()` → `User`

### Issue #3 — Laravel API Resources
- Create `App\Http\Resources\TicketResource` (includes loaded `requester`, `assignee`, `comments`)
- Create `App\Http\Resources\CommentResource` (includes loaded `user`; omits `is_internal` when customer)
- Create `App\Http\Resources\TicketCollection` for index responses

### Issue #4 — TicketController (Full CRUD + Filtering)
- `GET /api/tickets` — index with query filters:
  - `?status=open`, `?priority=high`, `?assignee_id=3`
  - `?search=keyword` — text search on `subject` or `description`
  - Returns `TicketCollection` wrapped in `TicketResource`
- `POST /api/tickets` — store new ticket (customer + agent + admin)
  - Auto-set `requester_id` = `auth()->id()`, `organization_id` via `BelongsToTenant`
- `GET /api/tickets/{ticket}` — show single ticket with comments
  - Role-gated: customers can only view tickets where `requester_id == auth()->id()`
  - Agents/admins can view any ticket in their org
- `PUT /api/tickets/{ticket}` — update status, priority, assignee_id (agent + admin only)
- `DELETE /api/tickets/{ticket}` — destroy (admin only)

### Issue #5 — CommentController (Threaded Replies)
- `GET /api/tickets/{ticket}/comments` — list comments
  - Customers: see only `is_internal = false` comments
  - Agents/admins: see all comments
- `POST /api/tickets/{ticket}/comments` — add a comment
  - Customers: `is_internal` forced to `false`
  - Agents/admins: can set `is_internal = true`
- `DELETE /api/comments/{comment}` — delete own comment or admin delete any

### Issue #6 — Role-Based Access Middleware / Gates
- Reuse existing `RoleMiddleware`
- Add controller-level checks:
  - `customer` — can create tickets, view own tickets, add public comments
  - `agent`/`admin` — full org ticket visibility, update tickets, add internal notes
- `TicketPolicy` (or inline gates) for authorization:
  - `view` — customer: own tickets only; agent/admin: any org ticket
  - `update`/`delete` — admin unrestricted; agent can update but not delete; customer denied
  - `viewComments` — respects `is_internal`
  - `createComment` — any authenticated user in org

### Issue #7 — PHPUnit Feature Tests
- `TicketCrudTest.php` — covering:
  - Create ticket (customer, agent, admin)
  - Read own ticket vs read org ticket (role-gated)
  - Update ticket status/priority/assignee (agent/admin)
  - Delete ticket (admin only)
  - Filter by status, priority, assignee_id, text search
- `CommentTest.php` — covering:
  - Add public comment (any role)
  - Add internal note (agent/admin only)
  - Customer cannot see internal notes
  - Agent can see internal notes
- `TenantScopeTest.php` (existing) — ensure tickets/comments still scoped to org
- Run `php artisan test` → green

### Issue #8 — PR & Merge Checklist
- All migrations run cleanly on fresh DB
- Feature tests pass (`php artisan test`)
- Code follows PSR-12
- No hardcoded secrets

---

## Definition of Done
1. ✅ `Ticket` model updated with `tags` and full relationships.
2. ✅ `Comment` model + migration created with tenant scoping.
3. ✅ API Resources (`TicketResource`, `CommentResource`) produce clean JSON.
4. ✅ TicketController supports full CRUD + filtering + search.
5. ✅ CommentController supports threaded replies with role-gated visibility.
6. ✅ Role-based access enforced: customers see only own tickets + public comments.
7. ✅ PHPUnit feature tests cover CRUD, comments, filtering, and role gates.
8. ✅ All tests green (`26 passed, 63 assertions`).
9. ✅ Code committed to `main` at `~/forge2-shivam/` (user pushes manually).

## Deliverable
Sprint 2 implementation is **COMPLETE**. OpenClaw built the backend; Forge fixed JsonResource wrapping in show endpoints, wrote missing CommentTest, and verified all tests pass. User has committed the code to `main` and will push to GitHub manually.

**Status:** ✅ CLOSED — Ready for Sprint 3.
