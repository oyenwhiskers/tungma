@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('admins.index') }}">Admins</a>
    <i class="bi bi-chevron-right"></i>
    <span>{{ $admin->name }}</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">{{ $admin->name }}</h2>
    <div class="text-muted">Administrator details and information</div>
  </div>
  <div>
    <form method="post" action="{{ route('password.resetToDefault', $admin) }}" class="d-inline me-2">
      @csrf
      <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Reset password to default?')">
        <i class="bi bi-key"></i> Reset Password
      </button>
    </form>
    <a href="{{ route('admins.edit', $admin) }}" class="btn btn-primary me-2">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('admins.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-person-vcard me-2"></i> Admin Information
            </div>
            <div class="tm-card-body p-0">
                <div class="company-info-grid">
                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-person"></i>
                      <span>Full Name</span>
                    </div>
                    <div class="info-value">
                      <strong>{{ $admin->name }}</strong>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-at"></i>
                      <span>Username</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->username ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-envelope"></i>
                      <span>Email</span>
                    </div>
                    <div class="info-value">
                      @if($admin->email)
                          <a href="mailto:{{ $admin->email }}">{{ $admin->email }}</a>
                      @else
                          —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-telephone"></i>
                      <span>Contact Number</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->contact_number ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-calendar"></i>
                      <span>Date of Birth</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->date_of_birth ? \Carbon\Carbon::parse($admin->date_of_birth)->format('M d, Y') : '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-gender-ambiguous"></i>
                      <span>Gender</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->gender ? ucfirst($admin->gender) : '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-card-text"></i>
                      <span>IC Number</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->ic_number ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-briefcase"></i>
                      <span>Position</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->position ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-building"></i>
                      <span>Company</span>
                    </div>
                    <div class="info-value">
                      @if($admin->company)
                        <a href="{{ route('companies.show', $admin->company) }}">{{ $admin->company->name }}</a>
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-calendar-plus"></i>
                      <span>Created At</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->created_at->format('M d, Y h:i A') }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-clock-history"></i>
                      <span>Last Updated</span>
                    </div>
                    <div class="info-value">
                      {{ $admin->updated_at->format('M d, Y h:i A') }}
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-shield-check me-2"></i> Account Status
            </div>
            <div class="tm-card-body">
                <div class="mb-3">
                    <div class="text-muted small mb-1">Role</div>
                    <span class="badge bg-primary">Administrator</span>
                </div>
                <div class="mb-3">
                    <div class="text-muted small mb-1">Account Status</div>
                    <span class="badge bg-success">Active</span>
                </div>
                <div>
                    <div class="text-muted small mb-1">Last Login</div>
                    <div class="small">—</div>
                </div>
            </div>
        </div>
        
        <div class="tm-card mt-3">
            <div class="tm-card-header">
                <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
            </div>
            <div class="tm-card-body">
                <div class="d-grid gap-2">
                    <form method="post" action="{{ route('admins.destroy', $admin) }}" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 text-start">
                            <i class="bi bi-trash"></i> Delete Admin
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.company-info-grid {
    display: flex;
    flex-direction: column;
}

.info-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    transition: background-color 0.2s ease;
}

.info-row:nth-child(even) {
    background-color: #fafbfc;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row:hover {
    background-color: #f0f9ff;
}

.info-label {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--tm-muted);
    font-size: 14px;
    font-weight: 500;
}

.info-label i {
    font-size: 16px;
    width: 20px;
    text-align: center;
    color: var(--tm-primary);
    opacity: 0.7;
}

.info-value {
    display: flex;
    align-items: center;
    color: var(--tm-text);
    font-size: 14px;
    word-break: break-word;
}

.info-value strong {
    font-size: 16px;
    color: var(--tm-primary);
}

.info-value a {
    color: var(--tm-primary);
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .info-row {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 16px 20px;
    }
    
    .info-label {
        font-weight: 600;
    }
}
</style>
@endsection
