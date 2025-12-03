@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('policies.index') }}">Policies</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('policies.show', $policy) }}">{{ $policy->name }}</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Edit Policy</h2>
        <div class="text-muted">Update policy details and company</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="tm-card">
            <div class="tm-card-header"><i class="bi bi-file-earmark-text me-2"></i> Policy Details</div>
            <div class="tm-card-body">
                <form method="post" action="{{ route('policies.update', $policy) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-building"></i> Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">Select company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $policy->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-tag"></i> Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $policy->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-card-text"></i> Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4">{{ old('description', $policy->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="{{ route('policies.show', $policy) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="tm-card">
            <div class="tm-card-header"><i class="bi bi-info-circle"></i> Note</div>
            <div class="tm-card-body">
                <div class="small">Bills use the policy for the selected company at the time of bill creation.</div>
            </div>
        </div>
    </div>
</div>
@endsection
