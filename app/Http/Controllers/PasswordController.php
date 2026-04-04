<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('password.change');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        AuditLog::create([
            'user_id' => $user->id,
            'module' => 'password',
            'action' => 'update',
            'description' => 'Password changed',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Password updated successfully.',
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Password updated successfully.');
    }

    public function hashCheckForm(): View
    {
        $this->ensureHashCheckerAccess();

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'active'])
            ->orderBy('name')
            ->limit(500)
            ->get();

        return view('password.hash-check', compact('users'));
    }

    public function hashCheckVerify(Request $request): RedirectResponse
    {
        $this->ensureHashCheckerAccess();

        $validated = $request->validate([
            'intent' => ['nullable', 'in:verify,reset'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'identifier' => ['nullable', 'string', 'max:150'],
            'password_hash' => ['nullable', 'string', 'min:20'],
            'plain_password' => ['nullable', 'string', 'min:1'],
            'new_password' => ['nullable', 'string', 'min:4', 'max:120'],
        ]);

        $intent = (string) ($validated['intent'] ?? 'verify');
        $identifierInput = trim((string) ($validated['identifier'] ?? ''));

        if ($intent === 'reset') {
            return $this->resetPasswordByLookup($request, $validated, $identifierInput);
        }

        $candidatePassword = (string) ($validated['plain_password'] ?? '');
        if ($candidatePassword === '') {
            return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
                'matched' => false,
                'message' => 'Please enter Password To Check before verification.',
            ]);
        }

        $hashInput = '';
        $sourceLabel = 'hash';

        if (! empty($validated['user_id'])) {
            $selectedUser = User::query()
                ->select(['id', 'name', 'email', 'password'])
                ->find((int) $validated['user_id']);

            if (! $selectedUser || empty($selectedUser->password)) {
                return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'Selected user not found or password hash missing.',
                ]);
            }

            $hashInput = (string) $selectedUser->password;
            $sourceLabel = 'user: '.$selectedUser->name.' ('.$selectedUser->email.')';
        } elseif ($identifierInput !== '') {
            $matchedUsers = $this->resolveUsersByIdentifier($identifierInput);

            if ($matchedUsers->count() === 0) {
                return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'No user found for this Name/Roll No/Admission No/Email/Employee ID.',
                ]);
            }

            if ($matchedUsers->count() > 1) {
                return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'Multiple users matched. Please choose exact user from dropdown.',
                ]);
            }

            $selectedUser = $matchedUsers->first();
            if (! $selectedUser || empty($selectedUser->password)) {
                return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'Matched user found, but password hash is missing.',
                ]);
            }

            $hashInput = (string) $selectedUser->password;
            $sourceLabel = 'identifier user: '.$selectedUser->name.' ('.$selectedUser->email.')';
        } else {
            if (empty($validated['password_hash'])) {
                return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'Please select a user, or enter Name/Roll No/Admission No/Email/Employee ID, or provide a password hash.',
                ]);
            }

            // Normalize copy/pasted DB hash input (trim, strip wrapping quotes, remove whitespace/newlines)
            $hashInput = trim((string) $validated['password_hash']);
            $hashInput = trim($hashInput, "\"'");
            $hashInput = preg_replace('/\s+/', '', $hashInput) ?: '';
        }

        $isRecognizedHash = str_starts_with($hashInput, '$2y$')
            || str_starts_with($hashInput, '$2a$')
            || str_starts_with($hashInput, '$2b$')
            || str_starts_with($hashInput, '$argon2i$')
            || str_starts_with($hashInput, '$argon2id$');

        if (! $isRecognizedHash) {
            return back()->withInput($request->except('plain_password'))->with('hash_check_result', [
                'matched' => false,
                'message' => 'Invalid hash format. Please copy exact value from users.password column only.',
            ]);
        }

        $isMatch = Hash::check($candidatePassword, $hashInput);

        return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
            'matched' => $isMatch,
            'message' => $isMatch
                ? 'Password matches this '.$sourceLabel.'.'
                : 'Password does not match this '.$sourceLabel.'.',
        ]);
    }

    private function resetPasswordByLookup(Request $request, array $validated, string $identifierInput): RedirectResponse
    {
        if (empty($validated['user_id']) && $identifierInput === '') {
            return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
                'matched' => false,
                'message' => 'Password reset ke liye user select karein ya Name/Roll/Mobile/Email/Employee ID dein.',
            ]);
        }

        $user = null;
        if (! empty($validated['user_id'])) {
            $user = User::query()->with(['student', 'staff'])->find((int) $validated['user_id']);
        } else {
            $matchedUsers = $this->resolveUsersByIdentifier($identifierInput);

            if ($matchedUsers->count() === 0) {
                return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'No user found for this Name/Roll No/Mobile/Email/Employee ID.',
                ]);
            }

            if ($matchedUsers->count() > 1) {
                return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
                    'matched' => false,
                    'message' => 'Multiple users matched. Please choose exact user from dropdown.',
                ]);
            }

            $user = $matchedUsers->first();
        }

        if (! $user) {
            return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_check_result', [
                'matched' => false,
                'message' => 'User not found.',
            ]);
        }

        $newPassword = trim((string) ($validated['new_password'] ?? ''));
        if ($newPassword === '') {
            $newPassword = $this->buildDefaultPasswordForUser($user, $identifierInput);
        }

        $user->password = Hash::make($newPassword);
        $user->must_change_password = true;
        $user->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'module' => 'password',
            'action' => 'reset',
            'description' => 'Password reset via hash checker utility for user '.$user->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->withInput($request->except('plain_password', 'new_password'))->with('hash_reset_result', [
            'ok' => true,
            'message' => 'Password reset done. Naya password neeche diya gaya hai (isse user ko share karein).',
            'user' => $user->name.' ('.$user->email.')',
            'password' => $newPassword,
        ]);
    }

    private function buildDefaultPasswordForUser(User $user, string $identifierInput): string
    {
        $studentRoll = trim((string) optional($user->student)->roll_no);
        if ($studentRoll !== '') {
            return $studentRoll;
        }

        $studentPhone = trim((string) (optional($user->student)->phone ?: optional($user->student)->guardian_phone));
        if ($studentPhone !== '') {
            return $studentPhone;
        }

        $staffEmployee = trim((string) optional($user->staff)->employee_id);
        if ($staffEmployee !== '') {
            return $staffEmployee;
        }

        $userPhone = trim((string) $user->phone);
        if ($userPhone !== '') {
            return $userPhone;
        }

        if ($identifierInput !== '') {
            return $identifierInput;
        }

        return 'Temp@'.Str::upper(Str::random(6));
    }

    private function resolveUsersByIdentifier(string $identifier)
    {
        $needle = mb_strtolower($identifier);
        $like = '%'.$identifier.'%';
        $likeNeedle = '%'.$needle.'%';

        return User::query()
            ->with(['student:id,first_name,last_name,roll_no,admission_no,email', 'staff:id,first_name,last_name,employee_id,email'])
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('phone', 'like', '%'.$identifier.'%')
            ->orWhereRaw('LOWER(name) = ?', [$needle])
            ->orWhereRaw('LOWER(name) LIKE ?', [$likeNeedle])
            ->orWhereHas('student', function ($query) use ($identifier, $needle): void {
                $query->where('roll_no', $identifier)
                    ->orWhere('admission_no', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->orWhere('guardian_phone', $identifier)
                    ->orWhere('roll_no', 'like', '%'.$identifier.'%')
                    ->orWhere('admission_no', 'like', '%'.$identifier.'%')
                    ->orWhere('phone', 'like', '%'.$identifier.'%')
                    ->orWhere('guardian_phone', 'like', '%'.$identifier.'%')
                    ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [$needle])
                    ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ['%'.$needle.'%']);
            })
            ->orWhereHas('staff', function ($query) use ($identifier, $needle): void {
                $query->where('employee_id', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->orWhere('employee_id', 'like', '%'.$identifier.'%')
                    ->orWhere('phone', 'like', '%'.$identifier.'%')
                    ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) = ?", [$needle])
                    ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ['%'.$needle.'%']);
            })
            ->limit(5)
            ->get(['id', 'name', 'email', 'password']);
    }

    private function ensureHashCheckerAccess(): void
    {
        $user = request()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403);
    }
}
