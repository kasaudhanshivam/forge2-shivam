# Sprint 01: Base API, Authentication & Multi-Tenancy

**Project:** PulseDesk  
**Sprint Goal:** Establish the foundational Laravel 11 backend with multi-tenant data isolation and token-based API authentication.  
**Tech Stack:** Laravel 11 + MySQL + Sanctum  
**Working Directory:** `~/forge2-shivam`

---

## Scoped Issues

### Issue #1 — Organization Model & Migration
- Create `Organization` model (`app/Models/Organization.php`)
- Migration: `organizations` table with `id`, `name`, `slug`, `created_at`, `updated_at`
- Factory & Seeder for test data

### Issue #2 — User Model, Migration & Tenant Relationship
- Extend default Laravel `User` model
- Migration: add `organization_id` (foreign key → `organizations.id`), `role` enum (`admin`, `agent`, `customer`)
- Update factory to link users to organizations

### Issue #3 — BelongsToTenant Global Scope / Trait
- Create `BelongsToTenant` trait (or global scope) that automatically scopes **all** Eloquent queries to the authenticated user’s `organization_id`
- Hook into `booted` lifecycle or use `addGlobalScope`
- Ensure `User` model itself is exempt (otherwise login breaks)

### Issue #4 — Laravel Sanctum API Authentication
- Install & configure `laravel/sanctum`
- API routes group with `auth:sanctum` middleware
- Login endpoint: accept email + password, return token
- Register endpoint: create user inside a new organization
- Logout endpoint: revoke current token

### Issue #5 — Role Middleware / Gates
- Define `Gate::define` or custom middleware for `admin`, `agent`, `customer`
- Example: `AdminOnly`, `AgentOrAbove`

### Issue #6 — Multi-Tenant Isolation Test (Pest / PHPUnit)
- Feature test: create two organizations (Org A, Org B)
- Create a user in Org A, authenticate via Sanctum
- Attempt to fetch a resource scoped to Org B → assert 403 or empty
- Ensure Org A user can only see Org A data
- Run `php artisan test` and ensure green

### Issue #7 — PR & Merge Checklist
- All migrations run cleanly on fresh database
- Feature tests pass (`php artisan test`)
- `.env.example` updated with required Sanctum / DB vars
- Code follows PSR-12

---

## Definition of Done
1. `Organization` and `User` models + migrations exist and relate correctly.
2. Every non-auth query is transparently scoped to the user’s `organization_id`.
3. Sanctum-issued tokens authenticate API requests.
4. Three roles exist and are enforceable.
5. Pest/PHPUnit feature test proves cross-tenant data leakage is impossible.
6. All tests green; PR opened against `main`.

## Deliverable
OpenClaw implements Issues #1–#6, runs tests, then opens a PR. After merge, Sprint 2 (Ticket CRUD) begins.
