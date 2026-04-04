@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card app-card">
                <div class="card-body p-4 p-lg-5">
                    <span class="eyebrow">Student Reset</span>
                    <h1 class="h3 mb-2">Reset Student Password</h1>
                    <p class="text-body-secondary mb-4">Use registered email/mobile, roll number, and guardian phone.</p>
                    <form method="POST" action="{{ route('student.password.reset') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email or Mobile</label>
                            <input type="text" name="login" value="{{ old('login') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Roll Number</label>
                            <input type="text" name="roll_no" value="{{ old('roll_no') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guardian Phone</label>
                            <input type="text" name="guardian_phone" value="{{ old('guardian_phone') }}" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection