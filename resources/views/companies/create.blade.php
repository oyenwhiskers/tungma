@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('companies.index') }}">Companies</a>
    <i class="bi bi-chevron-right"></i>
    <span>Create</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">New Company</h2>
        <div class="text-muted">Add a new company to your logistics network</div>
    </div>
    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="tm-card">
            <div class="tm-card-body">
                <form method="post" action="{{ route('companies.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Official registered company name</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 089218904">
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Primary phone number for this company</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Full company address">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Complete mailing address</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="company@example.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Primary contact email address</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Based In</label>
                        <input class="form-control @error('based_in') is-invalid @enderror" name="based_in" value="{{ old('based_in') }}" placeholder="e.g. New York, USA">
                        @error('based_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Location or city where the company is based</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Registration Number</label>
                        <input class="form-control @error('registration_number') is-invalid @enderror" name="registration_number" value="{{ old('registration_number') }}" placeholder="e.g. 12345678-X">
                        @error('registration_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Official company registration number</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SST Number</label>
                        <input class="form-control @error('sst_number') is-invalid @enderror" name="sst_number" value="{{ old('sst_number') }}" placeholder="e.g. A12-3456-78901234">
                        @error('sst_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Sales and Service Tax registration number</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Bill ID Prefix</label>
                        <input class="form-control @error('bill_id_prefix') is-invalid @enderror" name="bill_id_prefix" value="{{ old('bill_id_prefix') }}" placeholder="e.g. BILL, INV, ABC">
                        @error('bill_id_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Prefix used when creating new bills for this company (alphabets only, e.g., BILL, INV)</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Company
                        </button>
                        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="tm-card">
            <div class="tm-card-body">
                <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Tips</h6>
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2">Ensure company name matches official registration</li>
                    <li class="mb-2">Provide accurate contact information</li>
                    <li class="mb-2">Required fields are marked with <span class="text-danger">*</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
