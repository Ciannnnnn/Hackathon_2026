<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $role = in_array($request->query('role'), ['student', 'teacher', 'admin'], true)
            ? $request->query('role')
            : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? $request->query('status')
            : null;

        $users = User::query()
            ->with(['student', 'teacher'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role, fn ($query, string $role) => $query->where('role', $role))
            ->when($status, fn ($query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => compact('search', 'role', 'status'),
            'counts' => [
                'total' => User::query()->count(),
                'students' => User::query()->where('role', 'student')->count(),
                'teachers' => User::query()->where('role', 'teacher')->count(),
                'active' => User::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request, AccountProvisioningService $accounts): RedirectResponse
    {
        $user = $accounts->create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('status', "{$user->full_name}'s {$user->role->value} account was created successfully.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['status' => 'You cannot deactivate your own administrator account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with(
            'status',
            "{$user->full_name}'s account is now ".($user->is_active ? 'active.' : 'inactive.'),
        );
    }
}
