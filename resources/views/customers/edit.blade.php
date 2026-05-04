@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('customers.index') }}">Customers</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit Customer</span>
</div>

<div class="tm-header">
    <h2 class="mb-1">Edit Customer Profile</h2>
    <div class="text-muted">Update details for {{ $customer->name }}</div>
</div>

<div class="tm-card" style="max-width: 600px;">
    <div class="tm-card-body">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="form-label fw-bold">Customer / Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="contact_number" class="form-label fw-bold">Contact Number</label>
                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number', $customer->contact_number) }}">
                @error('contact_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="debtor_code" class="form-label fw-bold">Debtor Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('debtor_code') is-invalid @enderror" id="debtor_code" name="debtor_code" value="{{ old('debtor_code', $customer->debtor_code) }}" required>
                @error('debtor_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if(auth()->user()->role === 'super_admin')
            <div class="mb-4">
                <label class="form-label fw-bold">Assign to Company <span class="text-danger">*</span></label>
                <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $customer->company_id) == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">This customer will only be visible to this company.</div>
                @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @else
            <div class="mb-4">
                <label class="form-label fw-bold">Company</label>
                <input type="text" class="form-control bg-light" value="{{ $customer->company?->name ?? 'Global / N/A' }}" disabled>
            </div>
            @endif

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Customer</button>
            </div>
        </form>
    </div>
</div>
@endsection
