<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function __invoke(Request $request, DashboardDataService $dashboard): View
    {
        return view('dashboards.teacher', $dashboard->teacher($request->user()));
    }
}
