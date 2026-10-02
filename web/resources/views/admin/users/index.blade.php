@extends('layouts.dashboard', ['title' => 'User Accounts | EduPulse AI'])

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold text-cyan-600">Administration</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">User accounts</h1>
            <p class="mt-2 text-sm text-slate-500">Create accounts, update user details, control access, and securely reset passwords.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-cyan-200 hover:text-cyan-700"><x-icon name="dashboard" class="h-4 w-4" /> Admin overview</a>
    </div>

    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="All accounts" :value="$counts['total']" detail="Every role in the platform" icon="users" tone="indigo" />
        <x-metric-card label="Students" :value="$counts['students']" detail="Accounts with learner profiles" icon="book" tone="cyan" />
        <x-metric-card label="Teachers" :value="$counts['teachers']" detail="Accounts with instructor profiles" icon="chart" tone="amber" />
        <x-metric-card label="Active" :value="$counts['active']" detail="Accounts permitted to sign in" icon="check" tone="emerald" />
    </section>

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <p class="font-semibold">Please correct the account information.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(340px,0.8fr)_minmax(0,1.4fr)]">
        <article class="dashboard-panel overflow-hidden xl:sticky xl:top-28">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="font-semibold text-slate-900">Create an account</h2>
                <p class="mt-1 text-xs leading-5 text-slate-400">Set a temporary password and share it securely with the user.</p>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5 p-5 sm:p-6">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-xs font-semibold text-slate-600">First name</span><input name="first_name" value="{{ old('first_name') }}" maxlength="80" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Last name</span><input name="last_name" value="{{ old('last_name') }}" maxlength="80" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                </div>
                <label class="block"><span class="text-xs font-semibold text-slate-600">Email address</span><input type="email" name="email" value="{{ old('email') }}" maxlength="191" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                <label class="block">
                    <span class="text-xs font-semibold text-slate-600">Role</span>
                    <select data-account-role name="role" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                        <option value="student" @selected(old('role', 'student') === 'student')>Student</option>
                        <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                        <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                    </select>
                </label>

                <div data-role-fields="student" class="space-y-4 {{ old('role', 'student') === 'student' ? '' : 'hidden' }}">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block"><span class="text-xs font-semibold text-slate-600">Student number</span><input name="student_number" value="{{ old('student_number') }}" maxlength="40" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                        <label class="block"><span class="text-xs font-semibold text-slate-600">Grade level</span><input name="grade_level" value="{{ old('grade_level') }}" maxlength="40" placeholder="2nd Year" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    </div>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Program</span><input name="program" value="{{ old('program') }}" maxlength="120" placeholder="BS Information Technology" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Guardian email <span class="font-normal text-slate-400">(optional)</span></span><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" maxlength="191" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                </div>

                <div data-role-fields="teacher" class="space-y-4 {{ old('role') === 'teacher' ? '' : 'hidden' }}">
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Employee number</span><input name="employee_number" value="{{ old('employee_number') }}" maxlength="40" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Department <span class="font-normal text-slate-400">(optional)</span></span><input name="department" value="{{ old('department') }}" maxlength="120" placeholder="Information Technology" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Temporary password</span><input type="password" name="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold text-slate-600">Confirm password</span><input type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                </div>
                <p class="text-[11px] leading-5 text-slate-400">Use at least 12 characters with uppercase, lowercase, and a number.</p>
                <input type="hidden" name="is_active" value="0" />
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3.5"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" /><span class="text-sm font-medium text-slate-700">Allow this account to sign in</span></label>
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700"><x-icon name="check" class="h-4 w-4" /> Create account</button>
            </form>
        </article>

        <article class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-100 p-5 sm:p-6">
                <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_150px_150px_auto] md:items-end">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Search</span><input name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Name or email" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Role</span><select name="role" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"><option value="">All roles</option>@foreach (['student', 'teacher', 'admin'] as $role)<option value="{{ $role }}" @selected($filters['role'] === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</span><select name="status" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"><option value="">Any status</option><option value="active" @selected($filters['status'] === 'active')>Active</option><option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option></select></label>
                    <div class="flex gap-2"><button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Filter</button>@if (array_filter($filters))<a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-500">Clear</a>@endif</div>
                </form>
            </div>

            @if ($users->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left">
                        <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider text-slate-400"><tr><th class="px-6 py-3.5">User</th><th class="px-4 py-3.5">Role</th><th class="px-4 py-3.5">Identifier</th><th class="px-4 py-3.5">Status</th><th class="px-6 py-3.5 text-right">Access</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="px-6 py-4"><p class="text-sm font-semibold text-slate-800">{{ $user->full_name }}</p><p class="mt-0.5 text-xs text-slate-400">{{ $user->email }}</p></td>
                                    <td class="px-4 py-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-100">{{ $user->role->value }}</span></td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ $user->student?->student_number ?? $user->teacher?->employee_number ?? 'System account' }}</td>
                                    <td class="px-4 py-4"><span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $user->is_active ? 'text-emerald-700' : 'text-rose-600' }}"><span class="h-2 w-2 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" data-account-editor-toggle="account-editor-{{ $user->id }}" aria-expanded="false" class="rounded-lg bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100">Edit</button>
                                            @if (auth()->user()->is($user))
                                                <span class="text-xs font-medium text-slate-400">Current account</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}">@csrf @method('PATCH')<button class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $user->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr id="account-editor-{{ $user->id }}" data-account-editor class="hidden bg-slate-50/70">
                                    <td colspan="5" class="px-6 py-5">
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <h3 class="text-sm font-semibold text-slate-900">Edit {{ $user->full_name }}</h3>
                                                    <p class="mt-1 text-xs text-slate-400">The role is fixed to protect related academic records. Leave password fields empty to keep the current password.</p>
                                                </div>
                                                <span class="self-start rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-indigo-700">{{ $user->role->value }}</span>
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                <label class="block"><span class="text-xs font-semibold text-slate-600">First name</span><input name="first_name" value="{{ $user->first_name }}" maxlength="80" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                <label class="block"><span class="text-xs font-semibold text-slate-600">Last name</span><input name="last_name" value="{{ $user->last_name }}" maxlength="80" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                <label class="block"><span class="text-xs font-semibold text-slate-600">Email address</span><input type="email" name="email" value="{{ $user->email }}" maxlength="191" required autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>

                                                @if ($user->role->value === 'student')
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Student number</span><input name="student_number" value="{{ $user->student?->student_number }}" maxlength="40" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Grade level</span><input name="grade_level" value="{{ $user->student?->grade_level }}" maxlength="40" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Program</span><input name="program" value="{{ $user->student?->program }}" maxlength="120" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Guardian email</span><input type="email" name="guardian_email" value="{{ $user->student?->guardian_email }}" maxlength="191" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                @elseif ($user->role->value === 'teacher')
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Employee number</span><input name="employee_number" value="{{ $user->teacher?->employee_number }}" maxlength="40" required class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                    <label class="block"><span class="text-xs font-semibold text-slate-600">Department</span><input name="department" value="{{ $user->teacher?->department }}" maxlength="120" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                                @endif
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2">
                                                <label class="block"><span class="text-xs font-semibold text-slate-600">New password <span class="font-normal text-slate-400">(optional)</span></span><input type="password" name="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /><span class="mt-1.5 block text-[11px] text-slate-400">At least 12 characters with uppercase, lowercase, and a number.</span></label>
                                                <label class="block"><span class="text-xs font-semibold text-slate-600">Confirm new password</span><input type="password" name="password_confirmation" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm outline-none focus:border-cyan-400 focus:bg-white focus:ring-4 focus:ring-cyan-100" /></label>
                                            </div>

                                            <div class="flex flex-col gap-4 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                                @if (auth()->user()->is($user))
                                                    <input type="hidden" name="is_active" value="1" />
                                                    <p class="text-xs font-medium text-slate-500">Your current administrator account must remain active.</p>
                                                @else
                                                    <input type="hidden" name="is_active" value="0" />
                                                    <label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500" /><span class="text-sm font-medium text-slate-700">Allow this account to sign in</span></label>
                                                @endif
                                                <div class="flex gap-2 sm:justify-end">
                                                    <button type="button" data-account-editor-toggle="account-editor-{{ $user->id }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
                                                    <button type="submit" class="rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-700">Save changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $users->links() }}</div>@endif
            @else
                <div class="p-6"><x-empty-state title="No accounts found" message="Clear the filters or create the first account using the form." /></div>
            @endif
        </article>
    </section>

@endsection
