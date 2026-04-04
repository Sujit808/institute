@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Security Utility</span>
            <h1 class="h3 mb-1">Password Reset Utility</h1>
            <p class="text-body-secondary mb-0">
                Hash se original password nikalna possible nahi hota. Is utility se password verify bhi hota hai aur authorized users reset bhi kar sakte hain.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card app-card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if (session('hash_check_result'))
                        @php($result = session('hash_check_result'))
                        <div class="alert {{ $result['matched'] ? 'alert-success' : 'alert-danger' }} border-0">
                            <strong>{{ $result['matched'] ? 'Match' : 'No Match' }}:</strong>
                            {{ $result['message'] }}
                        </div>
                    @endif

                    @if (session('hash_reset_result'))
                        @php($resetResult = session('hash_reset_result'))
                        <div class="alert alert-warning border-0">
                            <strong>Password Reset Success:</strong> {{ $resetResult['message'] }}
                            <div class="mt-2"><strong>User:</strong> {{ $resetResult['user'] }}</div>
                            <div><strong>New Password:</strong> <code>{{ $resetResult['password'] }}</code></div>
                            <div class="small mt-1">Is password ko securely share karein. User next login par change karega.</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.hash-check.verify') }}" class="row g-3" autocomplete="off" id="passwordHashForm">
                        @csrf
                        <input type="hidden" name="intent" id="hashFormIntent" value="verify">

                        <div class="col-12">
                            <label for="user_id" class="form-label fw-semibold">Select User (Recommended)</label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">-- Select user from database --</option>
                                @foreach (($users ?? collect()) as $user)
                                    <option value="{{ $user->id }}" {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) - {{ ucfirst(str_replace('_', ' ', $user->role)) }}{{ $user->active ? '' : ' [inactive]' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">User select karoge to system khud DB se password hash le lega. Copy-paste error avoid hoga.</div>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="identifier" class="form-label fw-semibold">Name / Roll No / Admission No / Email / Employee ID</label>
                            <input
                                type="text"
                                id="identifier"
                                name="identifier"
                                class="form-control @error('identifier') is-invalid @enderror"
                                placeholder="Example: 1202 or 9A001 or mobile number"
                                value="{{ old('identifier') }}"
                                autocomplete="off"
                            >
                            <div class="form-text">Name, Roll No, Mobile, Admission No, Email, Employee ID me se koi bhi de sakte ho.</div>
                            @error('identifier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12" id="manualHashWrap">
                            <label for="password_hash" class="form-label fw-semibold">Password Hash</label>
                            <textarea
                                id="password_hash"
                                name="password_hash"
                                class="form-control @error('password_hash') is-invalid @enderror"
                                rows="4"
                                placeholder="$2y$12$..."
                                autocomplete="off"
                            >{{ old('password_hash') }}</textarea>
                            <div class="form-text">
                                Tip: phpMyAdmin/DB se <strong>users.password</strong> ka exact full hash copy karein. Spaces, quotes, ya truncated value se match fail hoga.
                            </div>
                            @error('password_hash')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="plain_password" class="form-label fw-semibold">Password To Check</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    id="plain_password"
                                    name="plain_password"
                                    class="form-control @error('plain_password') is-invalid @enderror"
                                    placeholder="Enter password"
                                    autocomplete="new-password"
                                    autocapitalize="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                >
                                <button class="btn btn-outline-secondary" type="button" id="togglePlainPassword" aria-label="Show or hide password">
                                    <i class="bi bi-eye" id="togglePlainPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="form-text">Yahan actual password enter karna zaroori hai. Verification hamesha isi entered password se hoga.</div>
                            @error('plain_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="new_password" class="form-label fw-semibold">New Password (Optional for Reset)</label>
                            <input
                                type="text"
                                id="new_password"
                                name="new_password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                placeholder="Blank chhodoge to default roll/mobile/employee id use hoga"
                                value="{{ old('new_password') }}"
                                autocomplete="off"
                            >
                            <div class="form-text">Ye field sirf reset action me use hoti hai.</div>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" onclick="document.getElementById('hashFormIntent').value='verify';">
                                <i class="bi bi-shield-check me-1"></i> Verify Password
                            </button>
                            <button type="submit" class="btn btn-warning" onclick="document.getElementById('hashFormIntent').value='reset';">
                                <i class="bi bi-arrow-repeat me-1"></i> Reset & Show Password
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card app-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 mb-2">Important</h2>
                    <ul class="small text-body-secondary mb-0">
                        <li>Password hashes one-way hote hain.</li>
                        <li>Original password show/recover nahi kiya ja sakta.</li>
                        <li>Is page se verification aur secure reset dono possible hain.</li>
                        <li>Access sirf Super Admin aur Admin ke liye hai.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('plain_password');
    const toggleBtn = document.getElementById('togglePlainPassword');
    const icon = document.getElementById('togglePlainPasswordIcon');
    const userSelect = document.getElementById('user_id');
    const identifierInput = document.getElementById('identifier');
    const manualHashWrap = document.getElementById('manualHashWrap');
    const hashInput = document.getElementById('password_hash');

    if (!input || !toggleBtn || !icon) {
        return;
    }

    const syncHashMode = function () {
        if (!userSelect || !manualHashWrap || !hashInput) {
            return;
        }

        const usingUser = userSelect && userSelect.value !== '';
        const usingIdentifier = identifierInput && identifierInput.value.trim() !== '';
        const useManualHash = !usingUser && !usingIdentifier;

        manualHashWrap.style.display = useManualHash ? '' : 'none';
        hashInput.required = useManualHash;
    };

    if (userSelect) {
        userSelect.addEventListener('change', syncHashMode);
    }
    if (identifierInput) {
        identifierInput.addEventListener('input', syncHashMode);
    }
    syncHashMode();

    toggleBtn.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    });
});
</script>
@endpush
@endsection
