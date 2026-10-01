@extends('layouts.dashboard', ['title' => 'Admin Dashboard | EduPulse AI'])

@section('content')
    <div>
        <p class="text-sm font-semibold text-cyan-600">Administration</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">System overview</h1>
        <p class="mt-2 text-sm text-slate-500">Administrative tools remain intentionally lightweight while the student and teacher MVP is completed.</p>
    </div>
    <section class="mt-7 grid gap-4 md:grid-cols-3">
        <x-metric-card label="Authentication" value="Active" detail="Role-secured session access is enabled" icon="check" tone="emerald" />
        <x-metric-card label="User roles" value="3" detail="Student, teacher, and administrator" icon="users" tone="indigo" />
        <x-metric-card label="System health" value="Ready" detail="Use /api/health for full diagnostics" icon="chart" tone="cyan" />
    </section>
@endsection
