<?php

namespace App\Http\Controllers;

use App\Services\LearningAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAnalyticsController extends Controller
{
    public function __invoke(Request $request, LearningAnalyticsService $analytics): View
    {
        return view('teacher.analytics.index', $analytics->teacher($request->user(), $request->integer('subject') ?: null));
    }
}
