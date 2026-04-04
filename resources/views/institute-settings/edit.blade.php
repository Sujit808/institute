@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Organization Control</span>
            <h1 class="h2 mb-1">Institute Setup</h1>
            <p class="text-body-secondary mb-0">Create your school/college/institute profile, manage branches, and map users/teachers to branches.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12">
            <div class="card app-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">1) Institute Profile</h2>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <form method="POST" action="{{ route('institute-settings.profile.update') }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type">
                                @foreach (['school' => 'School', 'college' => 'College', 'institute' => 'Institute'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('type', $organization->type ?? 'school') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Display Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $organization->name ?? '') }}" placeholder="Al-Noor Public School">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Short Name</label>
                            <input type="text" class="form-control @error('short_name') is-invalid @enderror" name="short_name" value="{{ old('short_name', $organization->short_name ?? '') }}" placeholder="ANPS">
                            @error('short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept=".jpg,.jpeg,.png,.webp">
                            @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $organization->phone ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $organization->email ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address', $organization->address ?? '') }}</textarea>
                        </div>
                        <div class="col-12 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="org_active" name="is_active" value="1" {{ old('is_active', $organization->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="org_active">Institute Active</label>
                            </div>
                            <button class="btn btn-primary" type="submit">Save Institute Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">2) Add Branch</h2>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <form method="POST" action="{{ route('institute-settings.branches.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Branch Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Main Campus" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control" name="code" placeholder="MC-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" placeholder="Lahore">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" placeholder="Branch address">
                        </div>
                        <div class="col-12 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">Create Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card app-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">Branch List</h2>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>City</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branches as $branch)
                                    <tr>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->code ?: '-' }}</td>
                                        <td>{{ $branch->city ?: '-' }}</td>
                                        <td><span class="badge text-bg-light border">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-body-secondary py-3">No branches yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card app-card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">3) User/Teacher Branch Mapping</h2>
                </div>
                <div class="card-body px-4 pb-4 pt-3">
                    <form method="POST" action="{{ route('institute-settings.mappings.update') }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Branch Access</th>
                                        <th>Primary Branch</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        @php
                                            $selected = collect(old('mappings.'.$user->id.'.branch_ids', $userBranchMap[$user->id]['branch_ids'] ?? []))->map(fn ($id) => (int) $id)->all();
                                            $primary = (int) old('mappings.'.$user->id.'.primary_branch_id', $userBranchMap[$user->id]['primary_branch_id'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $user->name }}</div>
                                                <div class="small text-body-secondary">{{ $user->email }}</div>
                                            </td>
                                            <td><span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($branches as $branch)
                                                        <label class="btn btn-sm btn-outline-secondary">
                                                            <input type="checkbox" class="form-check-input me-1" name="mappings[{{ $user->id }}][branch_ids][]" value="{{ $branch->id }}" {{ in_array((int) $branch->id, $selected, true) ? 'checked' : '' }}>
                                                            {{ $branch->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm" name="mappings[{{ $user->id }}][primary_branch_id]">
                                                    <option value="">None</option>
                                                    @foreach ($branches as $branch)
                                                        <option value="{{ $branch->id }}" {{ $primary === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-body-secondary py-3">No users found for branch mapping.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Save Mapping</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
