@extends('layouts.dashboard', ['title' => 'Admin Dashboard | EduPulse AI'])

@section('content')
    <div>
        <p class="text-sm font-semibold text-cyan-600">Administration</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">System overview</h1>
        <p class="mt-2 text-sm text-slate-500">Manage secure access for students, teachers, and administrators.</p>
    </div>
    <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="All accounts" :value="$metrics['users']" detail="Users provisioned in EduPulse" icon="users" tone="indigo" />
        <x-metric-card label="Students" :value="$metrics['students']" detail="Learner accounts and profiles" icon="book" tone="cyan" />
        <x-metric-card label="Teachers" :value="$metrics['teachers']" detail="Instructor accounts and profiles" icon="chart" tone="emerald" />
        <x-metric-card label="Inactive" :value="$metrics['inactive']" detail="Accounts currently blocked from access" icon="logout" tone="rose" />
    </section>

    <section class="dashboard-panel mt-6 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Account management</h2>
            <p class="mt-1 text-sm text-slate-500">Create role-specific accounts and control application access.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-600/20 transition hover:bg-cyan-700">Manage users <x-icon name="arrow" class="h-4 w-4" /></a>
    </section>
@endsection
