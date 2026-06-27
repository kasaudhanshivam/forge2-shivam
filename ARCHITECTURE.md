# PulseDesk Architecture

This document explains the key technical decisions behind PulseDesk's multi-tenant architecture, security model, and frontend design.

---

## 1. Multi-Tenancy

### Problem
PulseDesk is a SaaS platform where multiple organizations (tenants) share the same database but must never see each other's data. Every query must be automatically scoped to the authenticated user's organization.

### Solution: `BelongsToTenant` Trait + Global Scope

We implemented a reusable `BelongsToTenant` trait (`app/Traits/BelongsToTenant.php`) that applies a **global Eloquent scope** to every model that uses it:

```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('organization_id', auth()->user()->organization_id ?? 0);
        });
    }
}
```

### Models Using the Trait
- `Ticket` (`app/Models/Ticket.php`)
- `Comment` (`app/Models/Comment.php`)
- `Organization` (exempt from tenant scope for registration)

### Why This Works
- **Zero developer overhead**: Once the trait is added, ALL queries (including `Ticket::find()`, `Ticket::all()`, `Ticket::where(...)`) are automatically filtered by `organization_id`.
- **No manual `where()` clauses needed**: Controllers remain clean and focused on business logic.
- **Security by default**: Even if a developer forgets to add a `where('organization_id', ...)` clause, the global scope prevents data leakage.

### Exception: The `User` Model
The `User` model is **exempt** from the `BelongsToTenant` global scope because authentication must be unscoped (a user can log in before we know their organization). Instead, the `User` model uses the `organization_id` field as a foreign key, and policies enforce organization-based access.

---

## 2. Security: Role-Based Access Control (RBAC)

### Problem
Different user roles (Admin, Agent, Customer) need different permissions. A customer should only see their own tickets and public comments, while an admin should see everything.

### Solution: Laravel Policies + Middleware

We use **Laravel Policies** (`app/Policies/`) for resource-level authorization and **Middleware** (`app/Http/Middleware/RoleMiddleware.php`) for route-level access control.

#### Policies

**TicketPolicy** (`app/Policies/TicketPolicy.php`):
- `viewAny($user)`: Agents and admins can see all tickets in the org. Customers can only see their own.
- `view($user, $ticket)`: Agents and admins can view any ticket. Customers can only view their own (`$ticket->requester_id === $user->id`).
- `create($user)`: Any authenticated user.
- `update($user, $ticket)`: Only agents and admins. Customers cannot update tickets.
- `delete($user, $ticket)`: Only admins.

**CommentPolicy** (`app/Policies/CommentPolicy.php`):
- `viewAny($user)`: Customers see only public comments. Agents and admins see all.
- `create($user)`: Any authenticated user can add a comment to their own ticket. Agents and admins can comment on any ticket.
- `delete($user, $comment)`: Users can delete their own comments. Admins can delete any comment.

#### Middleware

```php
// RoleMiddleware.php
public function handle($request, Closure $next, ...$roles)
{
    if (!in_array($request->user()->role, $roles)) {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    return $next($request);
}
```

Registered in `app/Http/Kernel.php` as `'role'`.

### Why This Works
- **Granular control**: Policies define exactly what each role can do with each resource.
- **Testable**: Policies are tested independently in PHPUnit tests (`tests/Feature/TicketCrudTest.php`, `tests/Feature/CommentTest.php`).
- **Clean controllers**: Controllers call `$this->authorize('view', $ticket)` and Laravel handles the rest.

---

## 3. Frontend: React Router + Context API for Auth

### Problem
The frontend needs to manage authentication state (token, user object, role) across all components, protect routes, and redirect unauthenticated users.

### Solution: AuthContext + React Router

**AuthContext** (`frontend/src/context/AuthContext.jsx`):
- Stores `user` object and `token` in React Context + `localStorage`.
- `login(email, password)` → POST `/api/login`, stores token, sets Axios default `Authorization` header.
- `register(...)` → POST `/api/register`, same as login.
- `logout()` → POST `/api/logout`, clears `localStorage`, resets state, navigates to `/login`.
- **Axios Request Interceptor**: Automatically attaches `Authorization: Bearer <token>` from `localStorage` to every request.
- **Axios Response Interceptor**: Catches 401 responses → clears auth → redirects to `/login`.

**ProtectedRoute** (`frontend/src/components/ProtectedRoute.jsx`):
- Reads auth state from `AuthContext`.
- If no token, redirects to `/login`.
- Otherwise, renders children.

**React Router** (`frontend/src/App.jsx`):
- `/login` → `LoginPage` (public)
- `/register` → `RegisterPage` (public)
- `/dashboard` → `Dashboard` (protected)
- `/tickets` → `TicketBoard` (protected)
- `/tickets/:id` → `TicketDetail` (protected)
- `/` → Redirects to `/dashboard` if authenticated, else `/login`

### Why This Works
- **No prop drilling**: Any component can access auth state via `useAuth()` hook.
- **Token persistence**: Token survives page refresh via `localStorage`.
- **Automatic 401 handling**: If the token expires or is revoked, the user is immediately redirected to login.
- **Role-based UI**: Components conditionally render based on `user.role` (e.g., internal comment checkbox is only shown for agents/admins).

---

## 4. API Design

### RESTful Resource Routes

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/register` | Register new user + org | Public |
| POST | `/api/login` | Login, returns token | Public |
| POST | `/api/logout` | Revoke token | Sanctum |
| GET | `/api/tickets` | List tickets (with filters) | Sanctum |
| POST | `/api/tickets` | Create ticket | Sanctum |
| GET | `/api/tickets/{id}` | View single ticket | Sanctum |
| PUT | `/api/tickets/{id}` | Update ticket | Sanctum |
| DELETE | `/api/tickets/{id}` | Delete ticket | Sanctum |
| GET | `/api/tickets/{id}/comments` | List comments | Sanctum |
| POST | `/api/tickets/{id}/comments` | Add comment | Sanctum |
| DELETE | `/api/comments/{id}` | Delete comment | Sanctum |
| GET | `/api/dashboard` | Metrics (total, open, resolved, SLA) | Sanctum |

### API Resources

All API responses use **Laravel API Resources** (`app/Http/Resources/`) to ensure consistent JSON structure:
- `TicketResource` → wraps a single ticket with nested relationships (requester, assignee, comments).
- `TicketCollection` → wraps a paginated list of tickets.
- `CommentResource` → wraps a single comment with user details. Conditionally includes `is_internal` based on role.
- `UserResource` → wraps user data (id, name, email, role).

---

## 5. Testing Strategy

- **PHPUnit** (26 tests, 63 assertions) covering:
  - Tenant scoping (users cannot see other orgs' data)
  - Authentication (login, register, logout, token revocation)
  - Ticket CRUD (create, read, update, delete with role gating)
  - Comment CRUD (public vs internal comments, deletion permissions)
  - Dashboard metrics (org-scoped aggregation)
- **SQLite in-memory** for fast test execution (`phpunit.xml` config).
- **Factories** for realistic test data generation (`database/factories/`).

---

## 6. Database Schema

### Organizations
- `id`, `name`, `slug`, `created_at`, `updated_at`

### Users
- `id`, `name`, `email`, `password`, `role` (admin|agent|customer), `organization_id`, `remember_token`, `email_verified_at`, `created_at`, `updated_at`

### Tickets
- `id`, `subject`, `description`, `status` (open|pending|resolved|closed), `priority` (low|medium|high|urgent), `tags` (JSON), `requester_id`, `assignee_id`, `organization_id`, `created_at`, `updated_at`

### Comments
- `id`, `body`, `ticket_id`, `user_id`, `is_internal` (boolean), `organization_id`, `created_at`, `updated_at`

---

## Summary

PulseDesk's architecture prioritizes **security by default** (global tenant scoping, Laravel Policies), **developer productivity** (clean controllers, reusable traits, API Resources), and **testability** (comprehensive PHPUnit suite). The frontend uses modern React patterns (Context API, Router, Tailwind) for a responsive, auth-aware SPA experience.
