@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('staff.index') }}">Staff</a>
    <i class="bi bi-chevron-right"></i>
    <span>Create</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">New Staff</h2>
        <div class="text-muted">Create a new staff member account</div>
    </div>
    <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="tm-card">
            <div class="tm-card-body">
                <form method="post" action="{{ route('staff.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Staff member's full name</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Login username</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Email address for login</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input class="form-control @error('contact_number') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" placeholder="e.g. 0123456789">
                            @error('contact_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Phone number</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select @error('gender') is-invalid @enderror" name="gender">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IC Number</label>
                            <input class="form-control @error('ic_number') is-invalid @enderror" name="ic_number" value="{{ old('ic_number') }}" placeholder="e.g. 123456-12-1234">
                            @error('ic_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">National identification number</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Position</label>
                            <input class="form-control @error('position') is-invalid @enderror" name="position" value="{{ old('position') }}" placeholder="e.g. Delivery Driver">
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Job title or position</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Start date using system application</div>
                    </div>
                    
                    @if(auth()->user()->role === 'super_admin')
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select class="form-select @error('company_id') is-invalid @enderror" name="company_id" required>
                            <option value="">Select company</option>
                            @foreach(\App\Models\Company::all() as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Assign to a company</div>
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <div class="form-control" style="background-color: #f8f9fa; display: flex; align-items: center;">
                            <strong>{{ $company->name ?? 'N/A' }}</strong>
                        </div>
                        <div class="form-text">Staff will be assigned to your company</div>
                        <input type="hidden" name="company_id" value="{{ $company->id ?? '' }}">
                    </div>
                    @endif
                    
                    <div class="mb-4">
                        <label class="form-label">Password (Default) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="Enter default password" value="{{ old('password', 'TungMa@123') }}">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Default password for the staff account (minimum 8 characters)</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Staff
                        </button>
                        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">
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
                    <li class="mb-2">Required fields are marked with <span class="text-danger">*</span></li>
                    <li class="mb-2">Staff members have limited access to system features</li>
                    <li class="mb-2">Password can be reset later if needed</li>
                    <li class="mb-2">Username must be unique across all users</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
