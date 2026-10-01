@extends('layouts.app', ['title' => 'Admin Dashboard | EduPulse AI'])

@section('content')
    <x-dashboard-shell
        eyebrow="Administration"
        title="Welcome, {{ auth()->user()->first_name }}"
        description="Your account is authenticated and protected by the administrator role. User and system management tools can be added after the student and teacher MVP."
    >
        <x-dashboard-card label="Authentication" value="Secure" detail="Rate limiting helps prevent repeated login attempts." />
        <x-dashboard-card label="Authorization" value="Admin" detail="This route is restricted to administrator accounts." />
        <x-dashboard-card label="Priority" value="MVP first" detail="Student and teacher experiences remain the hackathon focus." />
    </x-dashboard-shell>
@endsection
