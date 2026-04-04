<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstituteSettingsController extends Controller
{
    public function edit(): View
    {
        $organization = Organization::query()->with('branches')->latest('id')->first();
        $branches = $organization?->branches()->orderBy('name')->get() ?? collect();

        $users = User::query()
            ->whereIn('role', ['super_admin', 'admin', 'hr', 'teacher'])
            ->orderBy('name')
            ->get();

        $userBranchMap = $users->mapWithKeys(function (User $user) {
            $branchIds = $user->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
            $primaryBranchId = $user->branches()->wherePivot('is_primary', true)->value('branches.id');

            return [
                $user->id => [
                    'branch_ids' => $branchIds,
                    'primary_branch_id' => $primaryBranchId ? (int) $primaryBranchId : null,
                ],
            ];
        });

        return view('institute-settings.edit', compact('organization', 'branches', 'users', 'userBranchMap'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['school', 'college', 'institute'])],
            'name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $organization = Organization::current() ?? new Organization;

        $payload = [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => (bool) $request->boolean('is_active', true),
            'updated_by' => $request->user()->id,
            'created_by' => $organization->exists ? $organization->created_by : $request->user()->id,
        ];

        if ($request->hasFile('logo')) {
            $payload['logo_path'] = $request->file('logo')->store('organization-logos', 'public');
        } elseif ($organization->exists) {
            $payload['logo_path'] = $organization->logo_path;
        }

        $organization->fill($payload);
        $organization->save();

        return back()->with('status', 'Institute profile updated successfully.');
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        $organization = Organization::current();
        abort_if(! $organization, 422, 'Please save institute profile first.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Branch::query()->create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => (bool) $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Branch created successfully.');
    }

    public function updateMappings(Request $request): RedirectResponse
    {
        $organization = Organization::current();
        abort_if(! $organization, 422, 'Please save institute profile first.');

        $validated = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.branch_ids' => ['nullable', 'array'],
            'mappings.*.branch_ids.*' => ['integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('organization_id', $organization->id))],
            'mappings.*.primary_branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('organization_id', $organization->id))],
        ]);

        $mappings = $validated['mappings'] ?? [];

        DB::transaction(function () use ($mappings): void {
            foreach ($mappings as $userId => $mapping) {
                $user = User::query()->find($userId);
                if (! $user) {
                    continue;
                }

                $branchIds = collect($mapping['branch_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
                $primaryBranchId = isset($mapping['primary_branch_id']) ? (int) $mapping['primary_branch_id'] : null;

                if ($primaryBranchId && ! $branchIds->contains($primaryBranchId)) {
                    $branchIds->push($primaryBranchId);
                }

                $syncPayload = $branchIds->mapWithKeys(function (int $branchId) use ($primaryBranchId): array {
                    return [
                        $branchId => ['is_primary' => $primaryBranchId === $branchId],
                    ];
                })->all();

                $user->branches()->sync($syncPayload);
            }
        });

        return back()->with('status', 'User and teacher branch mapping updated successfully.');
    }

    public function switchBranch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        $branchId = (int) $validated['branch_id'];
        $user = $request->user();

        $allowed = $user->isSuperAdmin() || $user->branches()->where('branches.id', $branchId)->exists();
        abort_unless($allowed, 403);

        $request->session()->put('active_branch_id', $branchId);

        return back()->with('status', 'Active branch switched successfully.');
    }
}
