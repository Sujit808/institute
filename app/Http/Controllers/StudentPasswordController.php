<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StudentPasswordController extends Controller
{
    public function edit(): View
    {
        return view('student.password-change');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Password updated successfully.',
            ]);
        }

        return redirect()->route('student.password.edit')->with('status', 'Password updated successfully.');
    }

    public function showResetForm(): View
    {
        return view('student.password-reset');
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'roll_no' => ['required', 'string'],
            'guardian_phone' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $login = trim((string) $validated['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()->where('role', 'student')->where($field, $login)->first();
        abort_unless($user?->student_id, 404);

        $student = Student::query()->findOrFail($user->student_id);

        if ((string) $student->roll_no !== (string) $validated['roll_no'] || (string) ($student->guardian_phone ?: $student->phone) !== (string) $validated['guardian_phone']) {
            return back()->withErrors(['login' => 'Student verification failed.'])->withInput($request->except('password', 'password_confirmation'));
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('login')->with('status', 'Student password reset successful. Please login.');
    }
}
