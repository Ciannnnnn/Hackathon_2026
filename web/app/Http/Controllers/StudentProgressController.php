<?php

namespace App\Http\Controllers;

use App\Services\LearningAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function __invoke(Request $request, LearningAnalyticsService $analytics): View
    {
        return view('student.progress.index', $analytics->student($request->user(), $request->integer('subject') ?: null));
    }
}
