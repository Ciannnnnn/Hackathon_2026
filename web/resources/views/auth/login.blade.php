@extends('layouts.app', ['title' => 'Sign in | EduPulse AI'])

@section('content')
    <div class="mx-auto grid min-h-screen max-w-7xl items-center gap-12 px-5 py-12 lg:grid-cols-2 lg:px-8">
        <section class="max-w-xl">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 font-semibold tracking-tight">
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cyan-400 font-black text-slate-950">EP</span>
                <span class="text-lg">EduPulse <span class="text-cyan-300">AI</span></span>
            </a>
            <p class="mt-12 text-sm font-semibold uppercase tracking-[0.25em] text-cyan-300">Academic support, made actionable</p>
            <h1 class="mt-4 text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">
                See where learners need help—and what to do next.
            </h1>
            <p class="mt-6 max-w-lg text-lg leading-8 text-slate-400">
                Sign in to access early support insights, personalized study plans, and learning analytics built for your role.
            </p>
        </section>

        <section class="mx-auto w-full max-w-md rounded-3xl border border-white/10 bg-white/[0.06] p-7 shadow-2xl shadow-cyan-950/30 backdrop-blur sm:p-9">
            <div>
                <p class="text-sm font-medium text-cyan-300">Welcome back</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Sign in to your account</h2>
                <p class="mt-2 text-sm text-slate-400">Use your EduPulse email and password.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-600 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-3 text-white outline-none transition placeholder:text-slate-600 focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/10"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-rose-300" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-400/20">
                    Sign in securely
                </button>
            </form>

            <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                Access is restricted to authorized students, teachers, and administrators.
            </p>
        </section>
    </div>
@endsection
