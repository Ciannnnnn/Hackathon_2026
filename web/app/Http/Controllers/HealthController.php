<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(SystemHealthService $health): JsonResponse
    {
        $result = $health->check();

        return response()->json(
            $result,
            $result['status'] === 'ok' ? 200 : 503,
        );
    }
}
