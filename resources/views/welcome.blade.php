@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-5">
        <div class="card app-card border-0 shadow-sm">
            <div class="card-body p-5 text-center">
                <h1 class="h3 mb-2">SchoolSphere Management System</h1>
                <p class="text-body-secondary mb-4">Use the secure login to continue to the dashboard.</p>
                <a class="btn btn-primary" href="{{ route('login') }}">Go to Login</a>
            </div>
        </div>
    </div>
@endsection
