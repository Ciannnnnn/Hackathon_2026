@extends('layouts.app', ['title' => 'Teacher Dashboard | EduPulse AI'])

@section('content')
    <x-dashboard-shell
        eyebrow="Teacher workspace"
        title="Welcome, {{ auth()->user()->first_name }}"
        description="Your account is authenticated and protected by the teacher role. Class analytics and student support tools arrive in the next phases."
    >
        <x-dashboard-card label="Authentication" value="Secure" detail="Session regenerated after login and invalidated on logout." />
        <x-dashboard-card label="Authorization" value="Teacher" detail="Students and administrators cannot access this route." />
        <x-dashboard-card label="Next" value="Dashboard" detail="Student metrics, support distribution, and recent activity." />
    </x-dashboard-shell>
@endsection
