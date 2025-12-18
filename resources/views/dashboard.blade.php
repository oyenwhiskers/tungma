@extends('layouts.app')

@section('content')
<div class="tm-header">
  <div>
    <h2 class="mb-1">Dashboard</h2>
    <div class="text-muted">
      @if(auth()->user()->role === 'super_admin')
        Overview of all logistics operations
      @else
        Overview of {{ auth()->user()->company->name ?? 'your company' }} operations
      @endif
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  @if(auth()->user()->role === 'super_admin')
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Companies</div>
            <div class="value">{{ $companies_count }}</div>
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
            <div class="value">{{ $admins_count }}</div>
          </div>
          <i class="bi bi-person-badge text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
  @else
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">My Company</div>
            <div class="value" style="font-size:18px;">{{ auth()->user()->company->name ?? 'N/A' }}</div>
          </div>
          <i class="bi bi-building text-muted" style="font-size:32px; opacity:0.15;"></i>
        </div>
      </div>
    </div>
  </div>
  @endif
  <div class="col-md-3">
    <div class="tm-card">
      <div class="tm-card-body tm-kpi">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="label">Staff</div>
            <div class="value">{{ $staff_count }}</div>
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
            <div class="label">Total Bills</div>
            <div class="value">{{ $bills_count }}</div>
          </div>
          <i class="bi bi-receipt text-muted" style="font-size:32px; opacity:0.15;"></i>
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
            <div class="value" style="font-size:24px;">RM {{ number_format($total_revenue, 2) }}</div>
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
          @if(auth()->user()->role === 'super_admin')
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
          @endif
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
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('analytics.index') }}">
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
          <dt class="text-muted mb-1">
            @if(auth()->user()->role === 'super_admin')
              Total Bills
            @else
              My Bills
            @endif
          </dt>
          <dd class="mb-2">{{ $bills_count }} bills</dd>
          <dt class="text-muted mb-1">
            @if(auth()->user()->role === 'super_admin')
              Total Users
            @else
              Company Users
            @endif
          </dt>
          <dd class="mb-2">{{ $active_users }} active users</dd>
          <dt class="text-muted mb-1">System Status</dt>
          <dd class="mb-0"><span class="badge bg-success">Operational</span></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
