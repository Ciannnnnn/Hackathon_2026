<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request, DashboardDataService $dashboard): View
    {
        return view('dashboards.student', $dashboard->student($request->user()));
    }
}
