<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPerformanceController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TeacherStudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/teacher/dashboard', TeacherDashboardController::class)
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [TeacherStudentController::class, 'show'])->name('students.show');
        Route::post('/students/{student}/performance', [StudentPerformanceController::class, 'store'])
            ->name('students.performance.store');
    });

    Route::get('/student/dashboard', StudentDashboardController::class)
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::view('/admin/dashboard', 'dashboards.admin')
        ->middleware('role:admin')
        ->name('admin.dashboard');
});
