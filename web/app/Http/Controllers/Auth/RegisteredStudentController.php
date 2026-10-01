<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Services\AccountProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredStudentController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        RegisterStudentRequest $request,
        AccountProvisioningService $accounts,
    ): RedirectResponse {
        $user = $accounts->create([
            ...$request->validated(),
            'role' => 'student',
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Welcome to EduPulse AI. Your student account is ready.');
    }
}
