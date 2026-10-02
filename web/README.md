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

## Subject and grade management

Administrators use `/admin/subjects` to create subject offerings, assign an active teacher, enroll students, update class membership, and activate or deactivate offerings. Existing enrollments are marked dropped when removed rather than deleted, preserving the origin of historical academic records.

Teachers use `/teacher/grades` to select one of their active subjects and record grades only for actively enrolled students. A grade entry stores the quiz, assignment, and activity components, attendance, late and missing submission counts, and optional weak topics as a dated performance snapshot. Overall grade and performance trend are calculated consistently, and every save refreshes the academic support assessment.

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

## Phase 9 document modules and extraction

Teachers manage subject learning materials at `/teacher/modules`. Uploads accept validated PDF and DOCX files up to `MAX_MODULE_SIZE_MB`, store them on Laravel's private local disk using generated names, and expose the original document only through the ownership-protected teacher download route. Legacy `.doc` files must be converted to `.docx` first.

Laravel extracts PDFs into page-aware chunks and DOCX files into numbered text sections, then persists them in `module_chunks`. The optional Python RAG service can still process PDFs when configured; DOCX extraction remains local to Laravel. Processing failures are recorded without leaking internals and can be retried from the module library. Encrypted documents and scanned image-only PDFs are rejected; OCR is not included.

Configure the integration in `.env`:

```env
RAG_SERVICE_ENABLED=false
RAG_SERVICE_URL=http://localhost:5002
RAG_SERVICE_TIMEOUT=20
RAG_EXTRACTION_DRIVER=local
MAX_MODULE_SIZE_MB=10
```

## Phase 10 retrieval-augmented tutor

Actively enrolled students use `/student/tutor` to ask questions within a selected subject. `ModuleChunkRetriever` performs subject-scoped lexical ranking over chunks belonging only to ready modules, and `TutorService` sends the highest-ranked passages plus bounded recent conversation history to `AiService`.

Each assistant message stores only its actual source references and short excerpts in `retrieved_context`. The interface renders those records as module-and-page source cards. When retrieval returns no match, the answer is marked as general guidance and `retrieved_context` remains null. Conversation and question requests verify student ownership, active enrollment, and subject consistency before retrieval or generation begins.

Run the test suite and code formatter with:

```powershell
php artisan test
php vendor/bin/pint --test
```
