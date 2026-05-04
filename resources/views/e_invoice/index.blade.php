@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>E-Invoice Requests</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
  <div>
    <h2 class="mb-1">E-Invoice Requests</h2>
    <div class="text-muted">List of bills where customers have successfully requested an e-voice and provided their TIN info.</div>
  </div>
  <div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportTaxEntityModal">
        <i class="bi bi-file-earmark-excel"></i> Export Tax Entity (AutoCount)
    </button>
  </div>
</div>

<!-- Export Tax Entity Modal -->
<div class="modal fade" id="exportTaxEntityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="GET" action="{{ route('e-invoice.export-preview') }}">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
              <i class="bi bi-file-earmark-excel text-success me-2"></i> Export Tax Entity
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-3">Filter the requests you want to include in the preview.</p>

          @if(auth()->user()->role === 'super_admin')
          <div class="mb-3">
            <label class="form-label font-weight-bold">Select Company</label>
            <select name="company_id" class="form-select">
                <option value="all">All Companies</option>
                @foreach(\App\Models\Company::all() as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
          </div>
          @endif

          <div class="mb-3">
            <label class="form-label font-weight-bold">Select Date</label>
            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
          </div>

          <div class="mb-3">
            <label class="form-label font-weight-bold">Status</label>
            <select name="status" class="form-select">
                <option value="pending">Pending Only</option>
                <option value="downloaded">Downloaded Only</option>
                <option value="all">All</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
              <i class="bi bi-search me-1"></i> Preview Debtors
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="tm-card">
    <div class="tm-card-header">
        <i class="bi bi-search me-2"></i> Search E-Invoice Requests
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('e-invoice.index') }}" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search bill code, customer name, TIN..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort') !== 'oldest' ? 'selected' : '' }}>Latest First</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="tm-card tm-table mt-3">
    <div class="tm-card-body">
        @if($bills->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th width="12%">Bill Code</th>
                    <th width="10%">Date</th>
                    <th width="20%">Customer (TIN Info)</th>
                    <th width="15%">Identity No</th>
                    <th width="10%">Status</th>
                    <th width="10%">Amount (RM)</th>
                    <th width="8%" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                <tr class="{{ $bill->eCustomer->is_exported ? 'table-light text-muted' : '' }}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt"></i>
                            <strong>{{ $bill->bill_code }}</strong>
                        </div>
                    </td>
                    <td>
                        {{ $bill->date->format('d M Y') }}
                    </td>
                    <td>
                        <div class="fw-bold">{{ $bill->eCustomer->customer_name ?? 'N/A' }}</div>
                        <div class="small {{ $bill->eCustomer->is_exported ? 'text-muted' : 'text-secondary' }}">TIN: {{ $bill->eCustomer->tin_number ?? 'N/A' }}</div>
                        <span class="badge bg-light text-dark border px-2 py-1 fs-6 mt-1">{{ $bill->eCustomer->identity_type ?? 'MyKAD' }}</span>
                    </td>
                    <td>
                        {{ $bill->eCustomer->customer_ic ?: $bill->eCustomer->business_reg_number }}
                    </td>
                    <td>
                        @if($bill->eCustomer->is_exported)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle"></i> Done</span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="bi bi-hourglass-split"></i> Pending</span>
                        @endif
                    </td>
                    <td>
                        <div class="fw-bold text-end pe-4">{{ number_format($bill->amount, 2) }}</div>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('bills.show', $bill) }}" class="btn btn-sm btn-outline-secondary" title="View Bill">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted" style="font-size:13px;">
                Showing {{ $bills->firstItem() ?? 0 }} to {{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} requests
            </div>
            <div>{{ $bills->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <div class="title">No e-Invoice requests found</div>
            <p>No requests matching your criteria.</p>
        </div>
        @endif
    </div>
</div>
@endsection
