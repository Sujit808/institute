@extends('layouts.app')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card app-card shadow-sm border-0">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <span class="eyebrow">Security</span>
                                <h2 class="h3 mb-1">Change Password</h2>
                                <p class="text-body-secondary mb-0">Update your account password to continue.</p>
                            </div>
                            @if (auth()->user()->must_change_password)
                                <span class="badge text-bg-warning">Required</span>
                            @endif
                        </div>

                        <div class="alert d-none" data-password-alert role="alert"></div>

                        <form method="POST" action="{{ route('password.change.update') }}" data-password-form>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="current_password">Current Password</label>
                                <div class="input-group">
                                    <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle data-target="current_password" aria-label="Show current password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="current_password">@error('current_password'){{ $message }}@enderror</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">New Password</label>
                                <div class="input-group">
                                    <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle data-target="password" aria-label="Show new password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="password">@error('password'){{ $message }}@enderror</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="password_confirmation">Confirm Password</label>
                                <div class="input-group">
                                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle data-target="password_confirmation" aria-label="Show confirm password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="password_confirmation"></div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Back</a>
                                <button class="btn btn-primary" type="submit" data-submit-btn>Save Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('[data-password-form]');
        const alertBox = document.querySelector('[data-password-alert]');
        const submitBtn = document.querySelector('[data-submit-btn]');

        const clearErrors = function () {
            if (!form) {
                return;
            }

            form.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });

            form.querySelectorAll('[data-error-for]').forEach(function (el) {
                el.textContent = '';
            });
        };

        const showAlert = function (message, type) {
            if (!alertBox) {
                return;
            }

            alertBox.className = 'alert alert-' + type;
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        };

        if (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearErrors();
                if (alertBox) {
                    alertBox.classList.add('d-none');
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                }

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const payload = await response.json();

                    if (response.ok && payload.ok) {
                        showAlert(payload.message || 'Password updated successfully.', 'success');
                        form.reset();
                        return;
                    }

                    if (response.status === 422 && payload.errors) {
                        Object.keys(payload.errors).forEach(function (field) {
                            const input = form.querySelector('[name="' + field + '"]');
                            const errorSlot = form.querySelector('[data-error-for="' + field + '"]');

                            if (input) {
                                input.classList.add('is-invalid');
                            }

                            if (errorSlot) {
                                errorSlot.textContent = (payload.errors[field] || [])[0] || 'Invalid value';
                            }
                        });
                        showAlert(payload.message || 'Please correct highlighted fields.', 'danger');
                        return;
                    }

                    showAlert((payload && payload.message) || 'Unable to update password right now.', 'danger');
                } catch (error) {
                    showAlert('Network error. Please try again.', 'danger');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Save Password';
                    }
                }
            });
        }

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                const icon = this.querySelector('i');
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';

                if (icon) {
                    icon.classList.toggle('bi-eye', !show);
                    icon.classList.toggle('bi-eye-slash', show);
                }

                this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    });
    </script>
@endsection
