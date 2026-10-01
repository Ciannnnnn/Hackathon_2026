# EduPulse AI

EduPulse AI is an AI-powered educational support platform for identifying students who may need additional academic support, explaining weak areas, creating personalized study plans, and grounding an AI tutor in teacher-provided learning materials.

## Project status

Phases 1 through 3 are complete. The normalized MySQL schema and deterministic demo data are ready; Laravel now has a schema-compatible data layer, resilient health diagnostics, secure session authentication, login throttling, and student/teacher/admin route authorization.

## Architecture

```text
web/             Laravel 12, Blade, Tailwind CSS, authentication, dashboards, API orchestration
ml-service/      Flask + scikit-learn academic support classifier
rag-service/     Flask PDF extraction and retrieval service
database/        MySQL schema and deterministic demo seed data
```

Laravel is the public application. It owns authentication, role authorization, MySQL access, file uploads, dashboard rendering, and Gemini calls. It communicates privately with the Python ML and RAG services. Production secrets are configured through the hosting provider and never exposed to the browser.

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 20+ and npm for Tailwind/Vite assets
- MySQL 8.0+ or a compatible MariaDB version
- Python 3.11+ for the later ML and RAG services

## Local setup

Install the database through phpMyAdmin, or from PowerShell:

```powershell
cmd /c "mysql -u root -p < database\schema.sql"
cmd /c "mysql -u root -p edupulse_ai < database\seed.sql"
```

The schema script recreates the `edupulse_ai` database. Do not run it over data that must be preserved.

For the complete hackathon demo, import `database/schema.sql` and then `database/seed.sql`. The Laravel migrations currently cover the authentication and academic core tables used by automated tests; feature-specific migrations will be added with their phases. Do not run the current migrations over a database already created from `schema.sql`.

Configure Laravel in `web/.env`. The committed template is `web/.env.example`; keep passwords and API keys only in the ignored `.env` file or the hosting provider's environment settings.

Run the application:

```powershell
cd web
composer install
npm install
npm run build
php artisan serve
```

Then open `http://localhost:8000`. The framework health endpoint is available at `http://localhost:8000/up`.

Database-backed pages require valid `DB_*` credentials. The landing page and file-based sessions can run before a database connection is configured.

Health endpoints:

- `GET /up` checks that Laravel is running and is suitable for a deployment liveness probe.
- `GET /api/health` checks the database connection, verifies the complete EduPulse schema, and reports integration configuration without exposing secrets. It returns HTTP 503 while the database is unavailable or incomplete.

On Railway, either configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`, or map Railway's MySQL connection URL to Laravel's `DB_URL` variable.

## Authentication

Open `http://localhost:8000/login` after importing the complete schema and seed data. Successful logins are redirected to the dashboard for the account's role. Inactive accounts and cross-role dashboard access are rejected.

Authentication uses Laravel's server-side session guard with CSRF protection, regenerated sessions after login, invalidated sessions on logout, bcrypt-compatible password verification, and per-email/IP login throttling. On an HTTPS deployment, set `SESSION_SECURE_COOKIE=true`.

## Demo data

The seed contains one admin, one teacher, ten students, two subjects, attendance, assignment submissions, quiz results, performance snapshots, support analyses, recommendations, study plans, module chunks, tutor history, and a generated quiz.

All seeded demo users use this password:

```text
EduPulse123!
```

- Teacher: `maria.reyes@edupulse.demo`
- High-support student: `alex.santos@edupulse.demo`
- Admin: `admin@edupulse.demo`

## Development phases

1. Project structure and database schema - complete
2. Laravel foundation and MySQL integration - complete
3. Authentication and role authorization - complete
4. Blade layouts, landing page, and dashboards
5. Performance records and student analysis
6. Python ML service and Laravel integration
7. Gemini integration
8. AI analysis and study plans
9. PDF upload and extraction
10. Retrieval-augmented AI tutor
11. AI-generated quizzes
12. Analytics, polish, deployment, and demo hardening
