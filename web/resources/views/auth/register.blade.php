@extends('layouts.app', ['title' => 'Create student account | EduPulse AI'])

@section('content')
    <div class="mx-auto grid min-h-screen max-w-7xl items-center gap-10 px-5 py-10 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
        <section class="max-w-lg">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 font-semibold tracking-tight">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cyan-400 font-black text-slate-950">EP</span>
                <span class="text-lg">EduPulse <span class="text-cyan-300">AI</span></span>
            </a>
            <p class="mt-10 text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">Start learning with support</p>
            <h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">Create your student account.</h1>
            <p class="mt-6 text-lg leading-8 text-slate-400">Track academic progress, review personalized learning insights, and access your study tools in one place.</p>
            <div class="mt-8 rounded-2xl border border-amber-300/20 bg-amber-300/5 p-4 text-sm leading-6 text-amber-100/80">
                Teacher and administrator accounts are issued by an EduPulse administrator. This form creates student accounts only.
            </div>
        </section>

        <section class="mx-auto w-full max-w-2xl rounded-3xl border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-cyan-950/30 backdrop-blur sm:p-9">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><p class="text-sm font-medium text-cyan-300">Student registration</p><h2 class="mt-2 text-2xl font-semibold text-white">Create account</h2></div>
                <a href="{{ route('login') }}" class="text-sm font-semibold text-cyan-300 transition hover:text-cyan-200">Already registered? Sign in</a>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-rose-300/20 bg-rose-300/10 p-4 text-sm text-rose-200" role="alert">
                    <p class="font-semibold">Please correct the information below.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">First name</span><input name="first_name" value="{{ old('first_name') }}" maxlength="80" required autofocus autocomplete="given-name" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Last name</span><input name="last_name" value="{{ old('last_name') }}" maxlength="80" required autocomplete="family-name" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                </div>
                <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Email address</span><input type="email" name="email" value="{{ old('email') }}" maxlength="191" required autocomplete="email" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Student number</span><input name="student_number" value="{{ old('student_number') }}" maxlength="40" required placeholder="2026-0011" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none placeholder:text-slate-600 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Grade level</span><input name="grade_level" value="{{ old('grade_level') }}" maxlength="40" required placeholder="2nd Year" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none placeholder:text-slate-600 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Program <span class="font-normal text-slate-500">(optional)</span></span><input name="program" value="{{ old('program') }}" maxlength="120" placeholder="BS Information Technology" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none placeholder:text-slate-600 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Guardian email <span class="font-normal text-slate-500">(optional)</span></span><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" maxlength="191" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Password</span><input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                    <label class="block"><span class="mb-2 block text-sm font-medium text-slate-200">Confirm password</span><input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10" /></label>
                </div>
                <p class="text-xs leading-5 text-slate-500">Use at least 12 characters with uppercase, lowercase, and a number.</p>
                <button type="submit" class="w-full rounded-xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-400/20">Create student account</button>
            </form>
        </section>
    </div>
@endsection
