@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('receivers.index') }}">Receivers</a>
    <i class="bi bi-chevron-right"></i>
    <span>Add New</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Add Preset Receiver</h2>
        <div class="text-muted">Register a receiver to use them quickly in bills.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tm-card">
            <div class="tm-card-body p-4">
                <form action="{{ route('receivers.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name / Business Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. John Doe or ABC Trading">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number') }}" placeholder="e.g. +60 12-345 6789">
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(auth()->user()->role === 'super_admin')
                    <div class="mb-3">
                        <label class="form-label">Assign to Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">-- Select Company --</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">This receiver will only be visible to this company.</div>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" class="form-control bg-light" value="{{ auth()->user()->company?->name ?? 'Global / N/A' }}" disabled>
                        <div class="form-text">This receiver will be assigned to your company.</div>
                    </div>
                    @endif

                    <div class="mt-4 pt-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg"></i> Create Receiver
                        </button>
                        <a href="{{ route('receivers.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
