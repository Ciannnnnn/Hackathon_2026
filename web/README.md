# EduPulse AI web application

This is the Laravel 12 application for EduPulse AI. It uses Blade and Tailwind CSS for the interface and will own authentication, authorization, MySQL access, uploads, Gemini integration, and orchestration of the Python ML and RAG services.

## Run locally

```powershell
composer install
npm install
npm run build
php artisan serve
```

Copy `.env.example` to `.env` when setting up a new checkout, run `php artisan key:generate`, and supply database credentials when database-backed features are needed.

Framework health check: `GET /up`.
