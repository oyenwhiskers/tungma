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
          <p class="small text-muted mb-3">Filter the requests you want to include in the preview. (This will show all matching entities regardless of status).</p>

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
            <label class="form-label font-weight-bold">Select Month</label>
            <input type="month" name="month" class="form-control" value="{{ request('month') }}">
          </div>

          <div class="text-center my-2 text-muted small">— OR Custom Date Range —</div>

          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label font-weight-bold">Start Date</label>
              <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label font-weight-bold">End Date</label>
              <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
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
        <i class="bi bi-search me-2"></i> Search & Filter E-Invoice Requests
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('e-invoice.index') }}" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-input" name="search" class="form-control" placeholder="Search bill code, customer name, TIN..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort') !== 'oldest' ? 'selected' : '' }}>Latest First</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Done</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>

            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text small">Month</span>
                    <input type="month" name="month" class="form-control" value="{{ request('month') }}" onchange="this.form.submit()">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text small">From</span>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text small">To</span>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-50">Filter Date</button>
                <a href="{{ route('e-invoice.export-csv', request()->query()) }}" class="btn btn-outline-success w-50" title="Export current filtered list as CSV">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<form id="bulk-action-form" method="POST" action="{{ route('e-invoice.bulk-action') }}">
    @csrf
    <input type="hidden" name="action" id="bulk-action" value="">
    <input type="hidden" name="scope" id="bulk-scope" value="selected">
    <input type="hidden" name="search" value="{{ request('search') }}">
    <input type="hidden" name="month" value="{{ request('month') }}">
    <input type="hidden" name="start_date" value="{{ request('start_date') }}">
    <input type="hidden" name="end_date" value="{{ request('end_date') }}">

    <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
        <div class="d-flex gap-2 align-items-center">
            @if($bills->count() > 0)
            <div class="d-flex align-items-center gap-2 ms-2">
                <select id="bulk-action-select" class="form-select form-select-sm" style="width: 200px;">
                    <option value="">Select Bulk Action...</option>
                    <option value="mark_done">Mark Selected as Done</option>
                    <option value="mark_pending">Mark Selected as Pending</option>
                </select>
                <button type="button" onclick="submitBulk('selected')" class="btn btn-sm btn-primary">
                    Apply
                </button>
            </div>
            @if(request('month') || request('start_date') || request('search'))
            <button type="button" onclick="submitBulk('all', 'mark_done')" class="btn btn-outline-success btn-sm ms-3">
                <i class="bi bi-check2-all me-1"></i> Mark All Filtered as Done ({{ $bills->total() }} requests)
            </button>
            <button type="button" onclick="submitBulk('all', 'mark_pending')" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Mark All Filtered as Pending ({{ $bills->total() }} requests)
            </button>
            @endif
            @endif
        </div>
    </div>

    <div class="tm-card tm-table mt-1">
        <div class="tm-card-body">
            @if($bills->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th width="4%">
                            <input type="checkbox" class="form-check-input" id="check-all-requests">
                        </th>
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
                            <input type="checkbox" name="ids[]" value="{{ $bill->eCustomer->id }}" class="form-check-input request-checkbox">
                        </td>
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
                            <div class="fw-bold">
                                <a href="#" class="customer-search-link text-decoration-none text-dark" data-search="{{ $bill->eCustomer->customer_name }}" title="Search this customer">
                                    {{ $bill->eCustomer->customer_name ?? 'N/A' }}
                                </a>
                            </div>
                            <div class="small {{ $bill->eCustomer->is_exported ? 'text-muted' : 'text-secondary' }}">
                                TIN: 
                                <a href="#" class="customer-search-link text-decoration-none text-primary fw-bold" data-search="{{ $bill->eCustomer->tin_number }}" title="Search this TIN">
                                    {{ $bill->eCustomer->tin_number ?? 'N/A' }}
                                </a>
                            </div>
                            <span class="badge bg-light text-dark border px-2 py-1 fs-6 mt-1">{{ $bill->eCustomer->customer_type ?? 'Individual' }}</span>
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
                            <form action="{{ route('e-invoice.toggle-status', $bill->eCustomer->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $bill->eCustomer->is_exported ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $bill->eCustomer->is_exported ? 'Mark as Pending' : 'Mark as Reviewed/Done' }}">
                                    <i class="bi {{ $bill->eCustomer->is_exported ? 'bi-arrow-counterclockwise' : 'bi-check2-all' }}"></i>
                                </button>
                            </form>
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
</form>
@endsection

@push('scripts')
<script>
    // Handle click on customer/TIN to search
    document.querySelectorAll('.customer-search-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const val = this.getAttribute('data-search');
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value = val;
                searchInput.form.submit();
            }
        });
    });

    // Checkbox handlers
    const checkAll = document.getElementById('check-all-requests');
    const checkboxes = document.querySelectorAll('.request-checkbox');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    function submitBulk(scope, action = null) {
        const form = document.getElementById('bulk-action-form');
        const scopeInput = document.getElementById('bulk-scope');
        const actionInput = document.getElementById('bulk-action');
        
        if (form && scopeInput && actionInput) {
            let actionVal = action;
            if (scope === 'selected') {
                actionVal = document.getElementById('bulk-action-select').value;
                if (!actionVal) {
                    alert('Please select a bulk action.');
                    return;
                }
            }

            scopeInput.value = scope;
            actionInput.value = actionVal;

            if (scope === 'selected') {
                const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                if (checkedCount === 0) {
                    alert('Please select at least one request.');
                    return;
                }
                const actionText = actionVal === 'mark_done' ? 'Done' : 'Pending';
                if (!confirm(`Are you sure you want to mark the ${checkedCount} selected requests as ${actionText}?`)) {
                    return;
                }
            } else if (scope === 'all') {
                const actionText = actionVal === 'mark_done' ? 'Done' : 'Pending';
                if (!confirm(`Are you sure you want to mark ALL {{ $bills->total() }} filtered requests as ${actionText}?`)) {
                    return;
                }
            }
            form.submit();
        }
    }
</script>
@endpush
