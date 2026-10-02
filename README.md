# EduPulse AI

EduPulse AI is an AI-powered educational support platform for identifying students who may need additional academic support, explaining weak areas, creating personalized study plans, and grounding an AI tutor in teacher-provided learning materials.

## Project status

All 12 MVP phases are complete. EduPulse now has a normalized demo database, secure role authentication, account provisioning, managed subjects and enrollments, a teacher gradebook, private PDF learning-material uploads and extraction, a source-grounded conversational AI tutor and quiz generator, responsive role-aware analytics, a real Random Forest classifier, Gemini integration, and persisted AI-generated academic explanations, recommendations, and seven-day study plans.

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
- Python 3.11+ for the ML and RAG services

## Local setup

Create the local database through phpMyAdmin, or from PowerShell, then import the portable schema and demo data:

```powershell
cmd /c "mysql -u root -p -e \"CREATE DATABASE IF NOT EXISTS edupulse_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\""
cmd /c "mysql -u root -p edupulse_ai < database\schema.sql"
cmd /c "mysql -u root -p edupulse_ai < database\seed.sql"
```

The schema script creates tables in whichever empty database you select. It never creates or drops the database. Do not import it over an existing populated schema.

For the existing local hackathon demo, import `database/schema.sql` and then `database/seed.sql`. For a new empty Laravel Cloud database, use `php artisan migrate --force`; the migrations now cover the complete application schema. Do not mix the two initialization methods in the same database.

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

Students can create their own account from `/register` or through the **Create an account** link on the sign-in page. Self-registration always creates a student role; submitted role values cannot elevate access. Teacher and administrator accounts remain restricted to the administrator-only `/admin/users` workspace, which also supports student provisioning and account activation controls.

## Dashboards

The teacher dashboard aggregates active enrollments and the latest performance snapshots into class metrics, support distribution, score trends, a prioritized student table, weak-topic frequency, and recent activity. The student dashboard presents current academic indicators, support context, weak topics, progress charts, recent quizzes, and the active seven-day study plan when those tables are available.

Dashboard charts use Chart.js through the Vite bundle. Empty and partially configured accounts receive useful empty states instead of hard failures.

Phase 12 adds dedicated analytics at `/teacher/analytics` and `/student/progress`. Teachers can filter analytics by their assigned subject and review performance, attendance, academic-support distribution, weak-topic frequency, and generated-quiz outcomes. Students receive a private subject-level view of their performance history, practice scores, and recurring focus topics. Administrators can inspect a secret-safe operational summary at `/admin/health`.

## Student performance and analysis

Teachers can open `GET /teacher/students` to search and filter learners by subject or academic support level. Each student analysis page shows the latest attendance, quiz, assignment, activity, submissions, trend, weak topics, recommendations, study plan, and historical performance chart.

Teachers can record or update a dated performance snapshot from the analysis page. Inputs are validated and restricted to students actively enrolled in one of the signed-in teacher's subjects. Laravel sends the seven academic indicators to the private ML service and stores the predicted support level, confidence, and model version. If the service is unavailable or returns invalid data, a transparent `rules_fallback` assessment keeps the workflow operational. Results describe educational support needs only and are never presented as medical, psychological, or behavioral diagnoses.

## Subjects, enrollments, and grades

Administrators manage the source of academic data from `/admin/subjects`. Each subject offering has a code, title, school year, term, assigned teacher, active status, and an explicit list of enrolled students. Administrators can change enrollment later; removed students are retained as dropped enrollments so historical records remain traceable.

Teachers use `/teacher/grades` to record dated quiz, assignment, activity, attendance, and submission indicators only for students actively enrolled in their own subjects. The displayed overall grade is the equal average of quiz, assignment, and activity scores. Saving grades calculates the trend from the preceding dated record and immediately refreshes the ML or rules-based support assessment. The student dashboard then shows the latest teacher-recorded grade rather than relying only on seed data.

## Machine learning service

The Flask service in `ml-service/` trains a deterministic `RandomForestClassifier` on 4,000 synthetic but correlated academic records. It exposes `GET /health` and validated `POST /predict` endpoints. The current trained model achieved 94.25% accuracy on its held-out synthetic test set; this metric demonstrates implementation quality and is not a claim of real-world educational validity.

Set up and start the service in a separate terminal:

```powershell
cd ml-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python train_model.py
python app.py
```

Laravel uses `ML_SERVICE_ENABLED`, `ML_SERVICE_URL`, and `ML_SERVICE_TIMEOUT` from `web/.env`. Keep the default URL `http://localhost:5001` for local development.

## Gemini generative AI

Phase 7 adds a server-side Gemini provider and a reusable AI service with methods for student analysis, seven-day study plans, quiz generation, topic explanations, and grounded tutor answers. Gemini requests use structured output where practical, and every response is validated before it can be consumed by later phases. API keys are sent only from Laravel and are never exposed to browser JavaScript.

Create a Gemini API key in Google AI Studio, then add it to `web/.env`:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-3.5-flash
GEMINI_FALLBACK_MODELS=gemini-3.1-flash-lite
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_TIMEOUT=15
GEMINI_MAX_OUTPUT_TOKENS=2048
AI_DEMO_FALLBACK=true
```

Clear cached configuration after changing the environment:

```powershell
cd web
php artisan config:clear
```

Sign in as an administrator and use the **Test Gemini** control on `/admin/dashboard`. If Gemini is missing, unavailable, rate-limited, or returns malformed output, the application can use clearly marked local demo fallback content instead of crashing. The Phase 8 workflow uses this layer to persist student explanations, recommendations, and study plans.

## AI insights and personalized study plans

Teachers can open a learner from `/teacher/students` and select **Generate AI insights** after a performance snapshot exists. EduPulse sends only subject and academic indicators to Gemini—names, email addresses, and student numbers are excluded from the prompt. The response is validated and saved as:

- a concise academic-support explanation and weak-topic list;
- prioritized recommended actions;
- one active seven-day study plan with a dated task for each day.

Refreshing insights archives the previous AI plan, replaces pending AI recommendations, and preserves teacher-created or already-in-progress guidance. Students immediately see the active plan and updated explanation on their dashboard. If Gemini is unavailable, the same workflow saves clearly reported demo fallback guidance so the hackathon flow remains usable.

## PDF learning modules and extraction

Phase 9 adds a teacher-only module library at `/teacher/modules`. Teachers select one of their assigned active subjects, upload a text-based PDF or DOCX file of up to `MAX_MODULE_SIZE_MB`, and Laravel stores it outside the public web directory with a generated filename. The original filename is retained only for the authorized download response. Legacy `.doc` files require conversion to `.docx` before upload.

By default, Laravel validates private PDF and DOCX files, rejects damaged or empty documents, extracts selectable text, and creates overlapping grounded chunks. PDF chunks retain page numbers; DOCX chunks use numbered text sections because Word pagination depends on the rendering environment. A separately deployed RAG service remains available for PDFs through `RAG_EXTRACTION_DRIVER=service`, while DOCX extraction always runs inside Laravel. Failed files remain visible with a safe error and a retry action. Teachers cannot list, retry, download, or delete another teacher's modules.

Start the RAG service in a separate terminal before uploading:

```powershell
cd rag-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python app.py
```

The optional service URL is `http://localhost:5002`. Use `RAG_EXTRACTION_DRIVER=local` for Laravel Cloud. Phase 9 supports text-based PDFs; OCR for scanned image documents is intentionally outside the current scope.

## Retrieval-augmented AI tutor

Phase 10 adds a student-only tutor at `/student/tutor`. Students can access only subjects in which they have an active enrollment. For every question, EduPulse searches text chunks from ready modules in the selected subject, ranks matching passages, and sends only the best passages, recent conversation history, and current question to Gemini.

Tutor answers are persisted with their conversation. Teacher-material references are retained internally for grounding and audit but are not displayed in the student chat interface. If no relevant passage is found, the tutor can still provide general guidance without fabricating a source. Retrieved document text is treated as untrusted reference content so instructions embedded inside a document cannot override the tutor's system rules.

Conversation access is student-scoped and subject-scoped. Students cannot open another learner's history, query an unenrolled subject, or retrieve passages across subject boundaries. The tutor can still use the configured demo fallback if Gemini is temporarily unavailable.

## Grounded AI quizzes

Phase 11 adds a teacher quiz workspace at `/teacher/quizzes` and a student practice area at `/student/quizzes`. A teacher chooses a ready module, topic, difficulty, question count, and question type: multiple choice, true/false, short answer, or mixed. Gemini must return the exact requested number and type of questions and cite the numeric source chunk supporting each one. EduPulse rejects incomplete questions, mismatched types, invalid answer choices, and source IDs that were not included in the prompt. Unlike explanatory features, quiz generation never saves demo fallback questions because an assessment must remain grounded in teacher material.

New quizzes can remain drafts while the teacher reviews the answer key, explanations, and source pages. Publishing makes the quiz available only to actively enrolled students in that subject. Student responses are graded immediately, saved as attempt history, and shown with correct answers, explanations, weak-topic guidance, and the module page to review. Drafts, other subjects, and another student's results are not accessible.

Quiz generation uses Laravel's `deferred` queue connection so the page can return before a slow Gemini response completes. No separate queue worker is required for this workflow. Keep `QUEUE_CONNECTION=deferred` in production; the completed quiz appears as a draft for teacher review.

## Production deployment

Deploy `web/` as the Laravel Cloud application directory and attach the Laravel Cloud MySQL database cluster to the same environment. Use the cluster connection details in Laravel Cloud's environment variables; the local XAMPP values in `web/.env` are not uploaded or reused.

After attaching an empty cluster, run `php artisan migrate --force` from the Laravel Cloud environment. This creates the complete schema and records its migration history. Keep `database/schema.sql` and `database/seed.sql` for the existing local/demo workflow; do not import those files into the same cluster after running migrations.

At minimum, configure production values for `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, the `DB_*` connection, `SESSION_SECURE_COOKIE=true`, and `GEMINI_API_KEY`. The ML and RAG URLs are optional supporting services; Laravel remains the only public application. See [DEPLOYMENT.md](DEPLOYMENT.md) for the Laravel Cloud checklist.

## Hackathon demo flow

1. Sign in as `maria.reyes@edupulse.demo` and open **Learning Analytics** to show the mixed class outcomes and Alex Santos's high academic-support need.
2. Open Alex from **Students**, record or review a performance snapshot, then generate AI insights and a seven-day plan.
3. Open **Modules** to show the private teacher PDF and extracted preview, then use **AI Quizzes** to generate, review, and publish a grounded assessment.
4. Sign in as `alex.santos@edupulse.demo`, open **AI Tutor**, ask about database normalization, and point out the cited module page.
5. Complete the published quiz, review answer explanations, then open **Progress** to show the attempt and recurring focus topics.
6. Sign in as the administrator and open **System Health** to show database and integration readiness without exposing credentials.

## Demo data

The seed contains one admin, one teacher, ten students, two subjects, attendance, assignment submissions, quiz results, performance snapshots, support analyses, recommendations, study plans, module chunks, tutor history, and a generated quiz. It is sample content for demonstrating the application; administrators and teachers can replace it with managed subjects, enrollments, and grade entries from the interface.

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
4. Blade layouts, landing page, and dashboards - complete
5. Performance records and student analysis - complete
6. Python ML service and Laravel integration - complete
7. Gemini integration - complete
8. AI analysis and study plans - complete
9. PDF upload and extraction - complete
10. Retrieval-augmented AI tutor - complete
11. AI-generated quizzes - complete
12. Analytics, polish, deployment, and demo hardening - complete
