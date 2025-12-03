@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('staff.index') }}">Staff</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Edit Staff</h2>
        <div class="text-muted">Update {{ $staff->name }} information</div>
    </div>
    <a href="{{ route('staff.show', $staff) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="tm-card">
            <div class="tm-card-body">
                <form method="post" action="{{ route('staff.update', $staff) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $staff->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username', $staff->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $staff->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number', $staff->contact_number) }}">
                            @error('contact_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth) }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select @error('gender') is-invalid @enderror" name="gender">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender', $staff->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $staff->gender) == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IC Number</label>
                            <input class="form-control @error('ic_number') is-invalid @enderror" name="ic_number" value="{{ old('ic_number', $staff->ic_number) }}">
                            @error('ic_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input class="form-control @error('position') is-invalid @enderror" name="position" value="{{ old('position', $staff->position) }}">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Company</label>
                        <select class="form-select @error('company_id') is-invalid @enderror" name="company_id">
                            <option value="">Select company</option>
                            @foreach(\App\Models\Company::all() as $company)
                                <option value="{{ $company->id }}" {{ old('company_id', $staff->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="{{ route('staff.show', $staff) }}" class="btn btn-outline-secondary">
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
                    <dd>{{ $staff->created_at->format('M d, Y h:i A') }}</dd>
                    <dt class="text-muted">Last Updated</dt>
                    <dd>{{ $staff->updated_at->format('M d, Y h:i A') }}</dd>
                </dl>
            </div>
        </div>
        
        <div class="tm-card mt-3">
            <div class="tm-card-body">
                <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Note</h6>
                <p class="small text-muted mb-0">To change password, use the "Reset Password" button on the staff details page.</p>
            </div>
        </div>
    </div>
</div>
@endsection
