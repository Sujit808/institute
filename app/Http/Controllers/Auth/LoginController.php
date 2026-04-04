<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        sendFailedLoginResponse as protected traitSendFailedLoginResponse;
    }

    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username(): string
    {
        return 'login';
    }

    protected function validateLogin(Request $request): void
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
    }

    protected function credentials(Request $request): array
    {
        $login = trim((string) $request->input('login'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return [
            $field => $login,
            'password' => $request->input('password'),
            'active' => true,
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }

        return redirect()->route($user->must_change_password ? 'password.change.edit' : 'dashboard');
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('security.login.max_attempts', 5));
    }

    public function decayMinutes(): int
    {
        return max(1, (int) config('security.login.decay_minutes', 10));
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $this->logAuthAttempt($request, 'login_failed', 'Invalid login credentials.');

        return $this->traitSendFailedLoginResponse($request);
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn($this->throttleKey($request));

        $this->logAuthAttempt($request, 'login_locked', 'Login temporarily locked due to too many failed attempts.');

        throw ValidationException::withMessages([
            $this->username() => [
                'Too many login attempts. Please try again after '.$seconds.' seconds.',
            ],
        ])->status(429);
    }

    private function logAuthAttempt(Request $request, string $action, string $description): void
    {
        try {
            AuditLog::create([
                'user_id' => null,
                'module' => 'auth',
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'created_by' => null,
                'updated_by' => null,
            ]);
        } catch (\Throwable) {
            // Never block login responses due to audit failures.
        }
    }
}
