@extends('layouts.app')

@section('content')
<div class="tm-header">
  <div>
    <h2 class="mb-1">Dashboard</h2>
    <div class="text-muted">Overview of your logistics operations</div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Companies</div>
            <div class="value">{{ \App\Models\Company::count() }}</div>
          </div>
          <i class="bi bi-building text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Admins</div>
            <div class="value">{{ \App\Models\User::where('role','admin')->count() }}</div>
          </div>
          <i class="bi bi-person-badge text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Staff</div>
            <div class="value">{{ \App\Models\User::where('role','staff')->count() }}</div>
          </div>
          <i class="bi bi-people text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Total Revenue</div>
            <div class="value" style="font-size:24px;">RM {{ number_format(\App\Models\Bill::sum('amount'), 2) }}</div>
          </div>
          <i class="bi bi-cash-stack text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="tm-card">
      <div class="tm-card-header">
        <i class="bi bi-lightning-charge-fill me-2" style="color:var(--tm-accent);"></i>
        Quick Actions
      </div>
      <div class="tm-card-body">
        <div class="row g-2">
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('companies.create') }}">
              <i class="bi bi-building"></i> New Company
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('admins.create') }}">
              <i class="bi bi-person-badge"></i> New Admin
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('staff.create') }}">
              <i class="bi bi-people"></i> New Staff
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('policies.create') }}">
              <i class="bi bi-file-earmark-text"></i> New Policy
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('bills.create') }}">
              <i class="bi bi-receipt"></i> New Bill
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-primary w-100 text-start" href="{{ route('analytics.index') }}">
              <i class="bi bi-graph-up"></i> View Analytics
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="tm-card">
      <div class="tm-card-header">
        <i class="bi bi-info-circle me-2" style="color:var(--tm-primary);"></i>
        System Info
      </div>
      <div class="tm-card-body">
        <dl class="mb-0" style="font-size:13px;">
          <dt class="text-muted mb-1">Recent Activity</dt>
          <dd class="mb-2">{{ \App\Models\Bill::latest()->count() }} bills this month</dd>
          <dt class="text-muted mb-1">Active Users</dt>
          <dd class="mb-2">{{ \App\Models\User::whereNull('deleted_at')->count() }} total users</dd>
          <dt class="text-muted mb-1">System Status</dt>
          <dd class="mb-0"><span class="badge bg-success">Operational</span></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
