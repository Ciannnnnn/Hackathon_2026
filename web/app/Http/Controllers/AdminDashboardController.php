<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboards.admin', [
            'metrics' => [
                'users' => User::query()->count(),
                'students' => User::query()->where('role', 'student')->count(),
                'teachers' => User::query()->where('role', 'teacher')->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
        ]);
    }
}
