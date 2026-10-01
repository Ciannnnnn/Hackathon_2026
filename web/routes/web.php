<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminSubjectController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AiIntegrationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredStudentController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentInsightController;
use App\Http\Controllers\StudentPerformanceController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherGradebookController;
use App\Http\Controllers\TeacherModuleController;
use App\Http\Controllers\TeacherStudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredStudentController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredStudentController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/teacher/dashboard', TeacherDashboardController::class)
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/modules', [TeacherModuleController::class, 'index'])->name('modules.index');
        Route::post('/modules', [TeacherModuleController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('modules.store');
        Route::post('/modules/{module}/retry', [TeacherModuleController::class, 'retry'])
            ->middleware('throttle:10,1')
            ->name('modules.retry');
        Route::get('/modules/{module}/download', [TeacherModuleController::class, 'download'])->name('modules.download');
        Route::delete('/modules/{module}', [TeacherModuleController::class, 'destroy'])->name('modules.destroy');
        Route::get('/grades', [TeacherGradebookController::class, 'index'])->name('grades.index');
        Route::post('/grades/{student}', [TeacherGradebookController::class, 'store'])->name('grades.store');
        Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [TeacherStudentController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/performance', [StudentPerformanceController::class, 'store'])
            ->name('students.performance.store');
        Route::post('/students/{student}/ai-insights', [StudentInsightController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('students.insights.store');
    });

    Route::get('/student/dashboard', StudentDashboardController::class)
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [AdminSubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}/enrollments', [AdminSubjectController::class, 'updateEnrollments'])
            ->name('subjects.enrollments.update');
        Route::patch('/subjects/{subject}/status', [AdminSubjectController::class, 'toggleStatus'])
            ->name('subjects.status');
        Route::post('/integrations/gemini/test', AiIntegrationController::class)->name('integrations.gemini.test');
    });
});
