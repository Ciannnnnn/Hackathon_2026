<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\View\View;

class AdminHealthController extends Controller
{
    public function __invoke(SystemHealthService $health): View
    {
        return view('admin.health', ['health' => $health->check()]);
    }
}
