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

## Phase 4 interface

The Blade interface includes a responsive landing page, mobile sidebar navigation, role-aware workspaces, reusable metric and status components, and Chart.js analytics. Teacher metrics are aggregated from active enrollments and performance/support records. Student dashboards also read quiz results and active study plans when the complete Phase 1 schema is present.

Primary routes:

- `/` - public landing page
- `/teacher/dashboard` - teacher analytics and student academic pulse
- `/student/dashboard` - personal learning pulse and study plan
- `/admin/dashboard` - lightweight system overview

## Phase 5 performance analysis

Teachers have a searchable and filterable student roster at `/teacher/students`. The protected student detail route `/teacher/students/{student}` includes performance metrics, history charts, weak topics, recommendations, active study-plan context, and a validated form for recording dated snapshots.

Teacher ownership is checked for both page access and writes; a teacher cannot inspect or update a learner outside their active classes.

## Account management

Students can self-register securely at `/register`; the public form always creates a student account and profile. Administrators can use `/admin/users` to create student, teacher, and administrator accounts, create matching role profiles, search existing accounts, and activate or deactivate access. Passwords must contain at least 12 characters with mixed-case letters and numbers.

## Phase 6 machine learning integration

When a teacher saves a performance snapshot, Laravel sends the seven validated indicators to `POST {ML_SERVICE_URL}/predict`. Valid predictions are stored with the `ml_only` source, confidence, and model version. Connection failures, non-success responses, and invalid payloads safely use the local `rules_fallback` assessment.

Configure the integration in `.env`:

```env
ML_SERVICE_ENABLED=true
ML_SERVICE_URL=http://localhost:5001
ML_SERVICE_TIMEOUT=5
```

## Phase 7 Gemini integration

The provider-neutral `AiService` supports student analysis, study-plan generation, quiz generation, weak-topic explanations, and tutor answers. `GeminiProvider` sends server-side REST requests using the `x-goog-api-key` header, supports schema-controlled JSON responses, records non-sensitive model/token metadata, and rejects malformed results.

Required local configuration:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-3.5-flash
GEMINI_FALLBACK_MODELS=gemini-3.1-flash-lite
AI_DEMO_FALLBACK=true
```

Run `php artisan config:clear` after updating `.env`. Administrators can check readiness and test the real connection from `/admin/dashboard`. The API key is never included in health responses, logs, rendered pages, or browser requests.

## Phase 8 AI insights and study plans

From `/teacher/students/{student}`, an authorized teacher can generate or refresh AI support insights for a selected subject. The workflow requires a performance snapshot, sends only academic indicators to the configured AI provider, validates every returned field, and persists the explanation, weak topics, recommendations, active study plan, and seven dated plan items in one database transaction.

The generation route is teacher-only, verifies active class ownership, and is rate-limited. Refreshing archives prior AI plans and replaces only pending AI recommendations; teacher-authored and in-progress guidance is preserved. The active result is visible from both the teacher analysis page and student dashboard.

Run the test suite and code formatter with:

```powershell
php artisan test
php vendor/bin/pint --test
```
