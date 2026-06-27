# PulseDesk

A multi-tenant SaaS ticketing system built with **Laravel 11**, **React 19**, **Tailwind CSS**, and **MySQL**. Designed for customer support teams with role-based access control (Admin, Agent, Customer) and full organization-based data isolation.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | React 19 + Vite + Tailwind CSS 4 |
| Database | MySQL 8.0 (production) / SQLite (testing) |
| Auth | Laravel Sanctum (API Tokens) |
| Testing | PHPUnit (26 tests, 63 assertions) |
| AI Orchestration | Hermes (Product Owner) + OpenClaw (Coder) via EastRouter |

---

## Exact Run Steps

### 1. Clone & Install Dependencies

```bash
git clone <repo-url> forge2-shivam
cd forge2-shivam/backend
composer install
cd ../frontend
npm install
```

### 2. Backend Environment Setup

```bash
cd ../backend
cp .env.example .env
php artisan key:generate
```

Edit `.env` to configure your database:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pulsedesk
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Migrate & Seed Demo Data

```bash
php artisan migrate --seed
```

This creates the full database schema and seeds:
- 1 Organization ("PulseDesk Demo")
- 1 Admin, 2 Agents, 2 Customers
- 12 Tickets with mixed statuses, priorities, and tags
- Threaded comments (public + internal notes)

### 4. Start the Backend

```bash
php artisan serve
```

Backend runs at `http://127.0.0.1:8000`

### 5. Start the Frontend

```bash
cd ../frontend
npm run dev
```

Frontend runs at `http://localhost:5173`

### 6. Default Admin Login

| Field | Value |
|-------|-------|
| Email | `admin@demo.com` |
| Password | `password` |

Other demo accounts:
- `agent1@demo.com` / `password`
- `agent2@demo.com` / `password`
- `customer1@demo.com` / `password`
- `customer2@demo.com` / `password`

---

## AI Stack

This project was built using an **AI-native orchestration workflow**:

- **Hermes** (Product Owner / Brain): Orchestrated the project, authored sprint plans, verified test results, and managed the build pipeline.
- **OpenClaw** (Hands-on Coder): Implemented all backend APIs, frontend components, factories, seeders, and test files via terminal and file tools.
- **EastRouter Models**: Powered by **DeepSeek-v4-pro** (reasoning/planning) and **GLM-5.1** (execution/coding).

The workflow followed a strict dual-agent protocol: Hermes authored specs and verified quality, OpenClaw executed code and posted status reports back to a coordination channel.

---

## Project Structure

```
forge2-shivam/
├── backend/          # Laravel 11 API
│   ├── app/          # Models, Controllers, Policies, Resources
│   ├── database/     # Migrations, Factories, Seeders
│   ├── routes/       # API routes
│   └── tests/        # PHPUnit Feature Tests
├── frontend/         # React 19 + Vite + Tailwind
│   ├── src/          # Components, Pages, Context, Hooks
│   └── index.html
└── sprints/          # Sprint documentation (01-05)
```

---

## License

MIT
