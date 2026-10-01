# EduPulse AI

EduPulse AI is an AI-powered educational support platform for identifying students who may need additional academic support, explaining weak areas, creating personalized study plans, and grounding an AI tutor in teacher-provided learning materials.

## Project status

Phase 1 is complete: the normalized MySQL schema and deterministic demo data are ready. The application architecture has been updated from React/Express to Laravel with Blade after the PHP stack decision. Laravel 12 is installed and boots on the available PHP 8.2 runtime.

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
2. Laravel foundation and MySQL integration - in progress
3. Authentication and role authorization
4. Blade layouts, landing page, and dashboards
5. Performance records and student analysis
6. Python ML service and Laravel integration
7. Gemini integration
8. AI analysis and study plans
9. PDF upload and extraction
10. Retrieval-augmented AI tutor
11. AI-generated quizzes
12. Analytics, polish, deployment, and demo hardening
