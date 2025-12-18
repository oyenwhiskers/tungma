@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('companies.index') }}">Companies</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Edit Company</h2>
        <div class="text-muted">Update {{ $company->name }} information</div>
    </div>
    <a href="{{ route('companies.show', $company) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="tm-card">
            <div class="tm-card-body">
                <form method="post" action="{{ route('companies.update', $company) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $company->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number', $company->contact_number) }}">
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3">{{ old('address', $company->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $company->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Based In</label>
                        <input class="form-control @error('based_in') is-invalid @enderror" name="based_in" value="{{ old('based_in', $company->based_in) }}" placeholder="e.g. New York, USA">
                        @error('based_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Location or city where the company is based</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Registration Number</label>
                        <input class="form-control @error('registration_number') is-invalid @enderror" name="registration_number" value="{{ old('registration_number', $company->registration_number) }}" placeholder="e.g. 12345678-X">
                        @error('registration_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Official company registration number</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SST Number</label>
                        <input class="form-control @error('sst_number') is-invalid @enderror" name="sst_number" value="{{ old('sst_number', $company->sst_number) }}" placeholder="e.g. A12-3456-78901234">
                        @error('sst_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Sales and Service Tax registration number</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Bill ID Prefix</label>
                        <input class="form-control @error('bill_id_prefix') is-invalid @enderror" name="bill_id_prefix" value="{{ old('bill_id_prefix', $company->bill_id_prefix) }}" placeholder="e.g. BILL, INV, ABC">
                        @error('bill_id_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Prefix used when creating new bills for this company (alphabets only, e.g., BILL, INV)</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="{{ route('companies.show', $company) }}" class="btn btn-outline-secondary">
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
                <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>History</h6>
                <dl class="small mb-0">
                    <dt class="text-muted">Created</dt>
                    <dd>{{ $company->created_at->format('M d, Y h:i A') }}</dd>
                    <dt class="text-muted">Last Updated</dt>
                    <dd>{{ $company->updated_at->format('M d, Y h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
