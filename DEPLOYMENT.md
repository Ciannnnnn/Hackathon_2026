# EduPulse AI on Laravel Cloud

## 1. Attach the database

In Laravel Cloud, select the intended application environment and attach the existing MySQL database cluster. Confirm that the environment has the connection values supplied by Laravel Cloud:

```env
DB_CONNECTION=mysql
DB_HOST=cluster-host
DB_PORT=3306
DB_DATABASE=cluster-database
DB_USERNAME=cluster-user
DB_PASSWORD=cluster-password
```

Do not copy the local XAMPP host (`127.0.0.1`) or port (`3307`) into production. Keep credentials only in Laravel Cloud's environment settings.

## 2. Initialize the empty cluster database

For a new, empty Laravel Cloud cluster, use the Laravel migrations. They now create the complete application schema, including academic activity, AI support, module, tutor, and generated-quiz tables.

Run this from the Laravel Cloud application environment after attaching the database:

```text
php artisan migrate --force
```

This is safer than importing the local SQL dump into a new environment because Laravel records every applied migration and can apply future schema changes incrementally.

The files in `database/schema.sql` and `database/seed.sql` are retained for the existing local/demo database workflow. Do not import them into the same database after running migrations. If the local SQL dump contains irreplaceable data that must be moved to production, back up the cluster first and use a separate, planned data migration instead of mixing the dump with Laravel migrations.

To load the committed hackathon demo records without Workbench or public database access, run this once after migration:

```text
php artisan db:seed --force
```

The seeder uses Laravel's attached private MySQL connection and refuses to run when the `users` table already contains data. It loads only the committed demo dataset; it does not copy later changes from your local XAMPP database.

## 3. Configure Laravel

Set these Laravel Cloud variables in addition to the attached database values:

```env
APP_NAME="EduPulse AI"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:replace_with_generated_value
APP_URL=https://your-application-domain
APP_TIMEZONE=Asia/Manila

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=90

AI_PROVIDER=gemini
GEMINI_API_KEY=replace_me
GEMINI_MODEL=gemini-3.5-flash
GEMINI_FALLBACK_MODELS=gemini-3.1-flash-lite
AI_DEMO_FALLBACK=true
```

Generate `APP_KEY` locally with `php artisan key:generate --show`, then store only its output in Laravel Cloud. Never commit production credentials.

Use `web/` as the Laravel application directory. Build frontend assets with `npm ci` and `npm run build`, then optimize Laravel with `php artisan optimize`.

For a Laravel-only deployment, use:

```env
ML_SERVICE_ENABLED=false
RAG_SERVICE_ENABLED=false
RAG_EXTRACTION_DRIVER=local
```

The rules fallback replaces the ML call. The `local` PDF extraction driver processes text-based PDFs directly inside Laravel, so new module uploads do not require a separate Python RAG deployment. Set `RAG_EXTRACTION_DRIVER=service` only when a reachable RAG service URL has been deployed.

### Run AI quiz generation in the background

AI quiz generation must use a queue worker in production so a slow Gemini response cannot exceed the web request timeout. The standard `jobs` and `failed_jobs` tables are already included in the migrations.

In the Laravel Cloud Production environment, add a background process to the application cluster with this command:

```text
php artisan queue:work database --queue=ai,default --sleep=1 --tries=2 --timeout=75 --max-time=3600
```

Keep `QUEUE_CONNECTION=database`, redeploy, and confirm the worker is running. The quiz form will then return immediately while the worker generates and saves the grounded quiz. Restart workers after deployments so they load the new application code.

## 4. Verify the deployment

1. Open `/up` and confirm HTTP 200.
2. Open `/api/health` and confirm the database status is `ready`.
3. Sign in with one account for each role.
4. Confirm a teacher can open analytics and a student can open progress.
5. If demo data was imported, use `EduPulse123!` for the seeded accounts.

If `/api/health` reports `schema_incomplete`, run `php artisan migrate --force` and inspect `php artisan migrate:status`. If it reports `unavailable`, recheck the attached cluster and injected `DB_*` variables.
