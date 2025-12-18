@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('companies.index') }}">Companies</a>
    <i class="bi bi-chevron-right"></i>
    <span>{{ $company->name }}</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">{{ $company->name }}</h2>
    <div class="text-muted">Company details and related information</div>
  </div>
  <div>
    <a href="{{ route('companies.edit', $company) }}" class="btn btn-primary me-2">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-info-circle me-2"></i> Company Information
            </div>
            <div class="tm-card-body p-0">
                <div class="company-info-grid">
                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-building"></i>
                      <span>Company Name</span>
                    </div>
                    <div class="info-value">
                      <strong>{{ $company->name }}</strong>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-telephone"></i>
                      <span>Contact Number</span>
                    </div>
                    <div class="info-value">
                      {{ $company->contact_number ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-geo-alt"></i>
                      <span>Address</span>
                    </div>
                    <div class="info-value">
                      {{ $company->address ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-envelope"></i>
                      <span>Email</span>
                    </div>
                    <div class="info-value">
                      @if($company->email)
                          <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                      @else
                          —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-geo-alt-fill"></i>
                      <span>Based In</span>
                    </div>
                    <div class="info-value">
                      {{ $company->based_in ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-file-earmark-text"></i>
                      <span>Registration Number</span>
                    </div>
                    <div class="info-value">
                      {{ $company->registration_number ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-receipt-cutoff"></i>
                      <span>SST Number</span>
                    </div>
                    <div class="info-value">
                      {{ $company->sst_number ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-hash"></i>
                      <span>Bill ID Prefix</span>
                    </div>
                    <div class="info-value">
                      <code>{{ $company->bill_id_prefix ?? '—' }}</code>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-calendar-plus"></i>
                      <span>Created At</span>
                    </div>
                    <div class="info-value">
                      {{ $company->created_at->format('M d, Y h:i A') }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-clock-history"></i>
                      <span>Last Updated</span>
                    </div>
                    <div class="info-value">
                      {{ $company->updated_at->format('M d, Y h:i A') }}
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="tm-card mb-3">
            <div class="tm-card-header">
                <i class="bi bi-bar-chart me-2"></i> Statistics
            </div>
            <div class="tm-card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <div class="text-muted small">Staff Members</div>
                        <div class="h3 mb-0 text-primary">{{ $company->users()->where('role', 'staff')->count() }}</div>
                    </div>
                    <i class="bi bi-people fs-2" style="opacity:0.15;"></i>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Bills</div>
                        <div class="h3 mb-0 text-primary">{{ $company->bills()->count() }}</div>
                    </div>
                    <i class="bi bi-receipt fs-2" style="opacity:0.15;"></i>
                </div>
            </div>
        </div>
        
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
            </div>
            <div class="tm-card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('staff.index') }}?company={{ $company->id }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-people"></i> View Staff
                    </a>
                    <a href="{{ route('bills.index') }}?company={{ $company->id }}" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-receipt"></i> View Bills
                    </a>
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
