<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $route = match ($request->user()->role) {
            UserRole::Student => 'student.dashboard',
            UserRole::Teacher => 'teacher.dashboard',
            UserRole::Admin => 'admin.dashboard',
        };

        return redirect()->route($route);
    }
}
