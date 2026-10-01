# EduPulse AI web application

This is the Laravel 12 application for EduPulse AI. It uses Blade and Tailwind CSS for the interface and owns authentication, authorization, MySQL access, uploads, Gemini integration, and orchestration of the Python ML and RAG services.

## Run locally

```powershell
composer install
npm install
npm run build
php artisan serve
```

Copy `.env.example` to `.env` when setting up a new checkout, run `php artisan key:generate`, and supply database credentials when database-backed features are needed.

Framework health check: `GET /up`.

Database and integration readiness: `GET /api/health`. This endpoint deliberately returns HTTP 503 when MySQL is unavailable or any of the complete EduPulse schema tables are missing. It never returns credentials.

## Phase 2 data layer

The Laravel `User` model uses the Phase 1 columns `first_name`, `last_name`, `role`, and `password_hash`. Core models and relationships are available for teachers, students, subjects, enrollments, performance snapshots, and academic support analyses.

## Phase 3 authentication

The application provides rate-limited session login at `GET /login`, secure logout through `POST /logout`, and role-protected dashboards for students, teachers, and administrators. Only active accounts can sign in or retain access to a role dashboard.

Production HTTPS deployments should set:

```env
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

Run the test suite and code formatter with:

```powershell
php artisan test
php vendor/bin/pint --test
```
