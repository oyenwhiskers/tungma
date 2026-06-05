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
            <a class="btn btn-primary w-100 text-start" href="{{ route('bills.create') }}">
              <i class="bi bi-receipt"></i> New Bill
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('customers.create') }}">
              <i class="bi bi-person-plus"></i> New Customer
            </a>
          </div>
          <div class="col-md-4">
            <a class="btn btn-outline-secondary w-100 text-start" href="{{ route('receivers.create') }}">
              <i class="bi bi-person-vcard"></i> New Receiver
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

@if($showStaffBreakdown)
<div class="row mt-4">
  <div class="col-12">
    <div class="tm-card">
      <div class="tm-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-people-fill text-primary" style="font-size: 20px;"></i>
          <span class="fs-5 fw-bold">Staff Sales & Bill Breakdown</span>
        </div>
        
        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center flex-wrap gap-2">
          @if(auth()->user()->role === 'super_admin')
          <div class="d-flex align-items-center gap-1">
            <span class="small text-muted text-nowrap">Company:</span>
            <select name="company_id" class="form-select form-select-sm" style="width: auto;">
              <option value="">All Companies</option>
              @foreach($companies as $c)
                <option value="{{ $c->id }}" {{ $selected_company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          @endif
          <div class="d-flex align-items-center gap-1">
            <span class="small text-muted text-nowrap">From:</span>
            <input type="date" name="start_date" class="form-control form-control-sm" style="width: auto;" value="{{ $start_date }}">
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="small text-muted text-nowrap">To:</span>
            <input type="date" name="end_date" class="form-control form-control-sm" style="width: auto;" value="{{ $end_date }}">
          </div>
          <div class="d-flex align-items-center gap-1">
            <input type="text" name="search_staff" class="form-control form-control-sm" style="width: 150px;" placeholder="Search staff..." value="{{ $search_staff }}">
          </div>
          <button type="submit" class="btn btn-sm btn-primary py-1 px-3">
            <i class="bi bi-filter"></i> Apply
          </button>
          <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary py-1 px-3">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
          </a>
        </form>
      </div>
      
      <div class="tm-card-body p-0">
        @if(!empty($staffStats))
        <div class="row g-2 p-3 bg-light border-bottom m-0">
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Total Bills</div>
              <div class="fs-5 fw-bold text-dark">{{ $staff_total_bills }}</div>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Cash Sales</div>
              <div class="fs-5 fw-bold text-dark">RM {{ number_format($staff_total_cash, 2) }}</div>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">COD Sales</div>
              <div class="fs-5 fw-bold text-dark">RM {{ number_format($staff_total_cod, 2) }}</div>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">QR Sales</div>
              <div class="fs-5 fw-bold text-info">RM {{ number_format($staff_total_qr, 2) }}</div>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Transfer Sales</div>
              <div class="fs-5 fw-bold text-success">RM {{ number_format($staff_total_transfer, 2) }}</div>
            </div>
          </div>
          <div class="col-md-2 col-6">
            <div class="p-2 border rounded bg-white text-center shadow-sm">
              <div class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Total Sales</div>
              <div class="fs-5 fw-bold text-primary">RM {{ number_format($staff_total_sales, 2) }}</div>
            </div>
          </div>
        </div>
        @endif
        @if(empty($staffStats))
          <div class="tm-empty-state">
            <i class="bi bi-people"></i>
            <div class="title">No staff found</div>
            <div class="text-muted">No staff members matched your query.</div>
          </div>
        @else
          <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size: 14px;">
              <thead class="table-light">
                <tr>
                  <th style="padding: 12px 20px;">Staff Name</th>
                  <th class="text-center" style="padding: 12px 10px;">Total Bills</th>
                  <th class="text-end" style="padding: 12px 10px;">Cash Sales</th>
                  <th class="text-end" style="padding: 12px 10px;">COD Sales</th>
                  <th class="text-end" style="padding: 12px 10px;">QR Sales</th>
                  <th class="text-end" style="padding: 12px 10px;">Transfer Sales</th>
                  <th class="text-center" style="padding: 12px 10px;">Voids</th>
                  <th class="text-end" style="padding: 12px 20px;">Total Revenue</th>
                  <th class="text-center" style="width: 120px; padding: 12px 20px;">Details</th>
                </tr>
              </thead>
              <tbody>
                @foreach($staffStats as $stat)
                  <tr class="align-middle">
                    <td style="padding: 16px 20px;">
                      <div class="fw-semibold text-dark">{{ $stat['staff']->name }}</div>
                      <small class="text-muted">{{ $stat['staff']->email ?? $stat['staff']->username }}</small>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark border">{{ $stat['total_bills'] }}</span>
                    </td>
                    <td class="text-end text-nowrap">RM {{ number_format($stat['cash'], 2) }}</td>
                    <td class="text-end text-nowrap">RM {{ number_format($stat['cod'], 2) }}</td>
                    <td class="text-end text-nowrap text-info">RM {{ number_format($stat['qr'], 2) }}</td>
                    <td class="text-end text-nowrap text-success">RM {{ number_format($stat['transfer'], 2) }}</td>
                    <td class="text-center">
                      @if($stat['void_count'] > 0)
                        <span class="badge bg-danger-subtle text-danger fw-bold border border-danger-subtle">{{ $stat['void_count'] }}</span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td class="text-end text-nowrap fw-bold text-primary" style="padding: 16px 20px;">
                      RM {{ number_format($stat['total_sales'], 2) }}
                    </td>
                    <td class="text-center" style="padding: 16px 20px;">
                      <button class="btn btn-sm btn-outline-secondary py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#staff-bills-{{ $stat['staff']->id }}" aria-expanded="false">
                        <i class="bi bi-receipt"></i> View
                      </button>
                    </td>
                  </tr>
                  
                  <!-- Expandable Collapsible Row for Bills List -->
                  <tr class="collapse" id="staff-bills-{{ $stat['staff']->id }}" style="background-color: #fafbfc;">
                    <td colspan="9" class="p-4 border-bottom">
                      <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                          <span class="fw-bold text-secondary">
                            <i class="bi bi-list-task me-1"></i> Bill Registry for {{ $stat['staff']->name }}
                          </span>
                          <span class="badge bg-secondary">{{ $stat['total_bills'] }} active bills</span>
                        </div>
                        <div class="card-body p-0">
                          @if($stat['bills']->isEmpty())
                            <div class="p-4 text-center text-muted">
                              <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary" style="opacity: 0.5;"></i>
                              No active bills recorded for this staff in the selected range.
                            </div>
                          @else
                            <div class="table-responsive">
                              <table class="table table-hover table-sm mb-0 align-middle" style="font-size: 13px;">
                                <thead class="table-light text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                  <tr>
                                    <th class="ps-3 py-2">Bill Code</th>
                                    <th class="py-2">Date</th>
                                    <th class="py-2">Sender</th>
                                    <th class="py-2">Receiver</th>
                                    <th class="py-2">Payment Method</th>
                                    <th class="py-2 text-center">Status</th>
                                    <th class="pe-3 py-2 text-end">Amount</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @foreach($stat['bills'] as $bill)
                                    <tr>
                                      <td class="ps-3 py-2">
                                        <a href="{{ route('bills.show', $bill->id) }}" class="fw-semibold text-decoration-none" target="_blank">
                                          {{ $bill->bill_code }}
                                        </a>
                                      </td>
                                      <td class="py-2">{{ $bill->date ? $bill->date->format('d M Y') : '' }}</td>
                                      <td class="py-2 text-truncate" style="max-width: 150px;">{{ $bill->sender_name }}</td>
                                      <td class="py-2 text-truncate" style="max-width: 150px;">{{ $bill->receiver_name }}</td>
                                      <td class="py-2 text-capitalize">
                                        @php
                                          $details = $bill->payment_details;
                                          if (is_string($details)) $details = json_decode($details, true);
                                          $method = strtolower($details['method'] ?? 'unpaid');
                                        @endphp
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                          @if($method === 'bank_transfer')
                                            Transfer
                                          @elseif($method === 'e_wallet' || $method === 'e_wallet_qr')
                                            QR
                                          @else
                                            {{ $method }}
                                          @endif
                                        </span>
                                      </td>
                                      <td class="py-2 text-center">
                                        @if($bill->is_paid)
                                          <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                        @else
                                          <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unpaid</span>
                                        @endif
                                      </td>
                                      <td class="pe-3 py-2 text-end fw-semibold">RM {{ number_format($bill->amount, 2) }}</td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                          @endif
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endif
@endsection
