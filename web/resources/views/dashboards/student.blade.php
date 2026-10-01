@extends('layouts.app', ['title' => 'Student Dashboard | EduPulse AI'])

@section('content')
    <x-dashboard-shell
        eyebrow="Student workspace"
        title="Welcome, {{ auth()->user()->first_name }}"
        description="Your account is authenticated and protected by the student role. Study plans, progress, quizzes, and the AI Tutor arrive in the next phases."
    >
        <x-dashboard-card label="Authentication" value="Secure" detail="Your password hash is never exposed to the browser." />
        <x-dashboard-card label="Authorization" value="Student" detail="Teacher and administrator pages are inaccessible." />
        <x-dashboard-card label="Next" value="Learning plan" detail="Personalized actions based on academic performance." />
    </x-dashboard-shell>
@endsection
