@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Bills</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Bills</h2>
        <div class="text-muted">Track and manage billing and invoices</div>
    </div>
    <div>
        <a href="{{ route('bills.deleted') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-trash"></i> Deleted Bills
        </a>
        <a href="{{ route('bills.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Bill
        </a>
    </div>
</div>

<div class="tm-card">
    <div class="tm-card-header">
        <i class="bi bi-funnel me-2"></i> Filters
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('bills.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ request('search') }}"
                           placeholder="Bill code, description, customer...">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Collected Status</label>
                <select name="collected_status" class="form-select">
                    <option value="">All</option>
                    <option value="collected" {{ request('collected_status') == 'collected' ? 'selected' : '' }}>Collected</option>
                    <option value="uncollected" {{ request('collected_status') == 'uncollected' ? 'selected' : '' }}>Uncollected</option>
                </select>
            </div>

            @if(auth()->user()->role !== 'admin')
            <div class="col-md-2">
                <label class="form-label small text-muted">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">All Companies</option>
                    @foreach($companies ?? [] as $company)
                        <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-2">
                <label class="form-label small text-muted">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="e_wallet_qr" {{ request('payment_method') == 'e_wallet_qr' ? 'selected' : '' }}>E-wallet/QR</option>
                    <option value="cod" {{ request('payment_method') == 'cod' ? 'selected' : '' }}>COD</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Date </label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>

            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                        @if(request()->hasAny(['search', 'payment_status', 'company_id', 'payment_method', 'date']))
                            <span class="align-self-center text-muted small ms-2">
                                <i class="bi bi-info-circle"></i> {{ $bills->total() }} result(s) found
                            </span>
                        @endif
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportAutoCountModal">
                            <i class="bi bi-file-earmark-excel"></i> Export AutoCount
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- AutoCount Export Modal -->
<div class="modal fade" id="exportAutoCountModal" tabindex="-1" aria-labelledby="exportAutoCountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportAutoCountModalLabel">
            <i class="bi bi-file-earmark-excel text-success me-2"></i> Export to AutoCount
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Select the date for which you want to export all bills to AutoCount format.</p>
        
        @if(auth()->user()->role === 'super_admin')
        <div class="mb-3">
          <label for="export_company_id" class="form-label font-weight-bold">Select Company</label>
          <select id="export_company_id" class="form-select">
              <option value="all">All Companies</option>
              @foreach($companies ?? \App\Models\Company::all() as $company)
                  <option value="{{ $company->id }}">{{ $company->name }}</option>
              @endforeach
          </select>
        </div>
        @else
        <input type="hidden" id="export_company_id" value="{{ auth()->user()->company_id }}">
        @endif

        <div class="row">
          <div class="col-6 mb-3">
            <label for="export_start_date" class="form-label font-weight-bold">Start Date</label>
            <input type="date" id="export_start_date" class="form-control" value="{{ date('Y-m-d') }}">
          </div>
          <div class="col-6 mb-3">
            <label for="export_end_date" class="form-label font-weight-bold">End Date</label>
            <input type="date" id="export_end_date" class="form-control" value="{{ date('Y-m-d') }}">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btn-download-autocount" class="btn btn-success">
            <i class="bi bi-download me-1"></i> Download Excel
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('btn-download-autocount').addEventListener('click', function() {
    const startDate = document.getElementById('export_start_date').value;
    const endDate = document.getElementById('export_end_date').value;
    const companyId = document.getElementById('export_company_id') ? document.getElementById('export_company_id').value : '';
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    const btn = this;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    // Redirect to download
    const exportUrl = `{{ route('bills.export-autocount') }}?start_date=${startDate}&end_date=${endDate}&company_id=${companyId}`;
    window.location.href = exportUrl;

    // Reset button after a short delay
    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportAutoCountModal'));
        if (modal) modal.hide();
    }, 2000);
});
</script>
@endpush


<div class="tm-card tm-table mt-3">
    <div class="tm-card-body">
        @if($bills->count() > 0)
        <form method="POST" action="{{ route('bills.bulk-action') }}" id="bulk-action-form">
            @csrf
            
            <div class="d-flex justify-content-between align-items-center mb-3 px-3 pt-3">
                <div class="d-flex align-items-center gap-2">
                    <select name="bulk_action" class="form-select form-select-sm" style="width: 200px;" required>
                        <option value="">Select Bulk Action...</option>
                        <option value="mark_paid">Mark as Paid</option>
                        <option value="mark_unpaid">Mark as Unpaid</option>
                        <option value="mark_collected">Mark as Collected</option>
                        <option value="mark_uncollected">Mark as Uncollected</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Apply this action to selected bills?');">
                        Apply
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="4%" class="text-center">
                                <input class="form-check-input" type="checkbox" id="checkAll">
                            </th>
                            <th>Bill Code</th>
                    <th>Date</th>
                    <th>Bus Departure</th>
                    <th>Amount</th>
                    <th>Company</th>
                    <th>Payment Type</th>
                    <th>Payment Status</th>
                    <th>Collected</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($bills as $bill)
                <tr>
                    <td class="text-center">
                        <input class="form-check-input row-check" type="checkbox" name="bill_ids[]" value="{{ $bill->id }}">
                    </td>
                    <td>
                        <a href="{{ route('bills.show', $bill) }}" class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt"></i>
                            <strong>{{ $bill->bill_code }}</strong>
                        </a>
                    </td>
                    <td>{{ $bill->date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $bill->busDeparture ? \Carbon\Carbon::parse($bill->busDeparture->departure_time)->format('h:i A') : '—' }}</td>
                    <td><strong>RM {{ number_format($bill->amount, 2) }}</strong></td>
                    <td>{{ $bill->company?->name ?? '—' }}</td>
                    <td>
                        @php
                            $payment = $bill->payment_details;
                            if (is_string($payment)) $payment = json_decode($payment, true);
                            if (is_string($payment)) $payment = json_decode($payment, true);
                            $payment = $payment ?: [];
                            $method = $payment['method'] ?? null;
                            $methodLabels = [
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'e_wallet_qr' => 'E-wallet/QR',
                                'cod' => 'COD',
                                'credit_card' => 'Credit Card',
                                'e_wallet' => 'E-Wallet'
                            ];
                        @endphp
                        {{ $methodLabels[$method] ?? ($method ? ucfirst(str_replace('_', ' ', $method)) : '—') }}
                    </td>
                    <td>{{ $bill->is_paid ? 'Paid' : 'Unpaid' }}</td>
                    <td>{{ $bill->is_collected ? 'Collected' : 'Uncollected' }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('bills.view-template', $bill) }}" class="btn btn-outline-success" title="View PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="{{ route('bills.edit', $bill) }}" class="btn btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        </form>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted" style="font-size:13px;">
                Showing {{ $bills->firstItem() ?? 0 }} to {{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} bills
            </div>
            <div>{{ $bills->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-receipt"></i>
            <div class="title">No bills found</div>
            <p>Create your first bill to get started</p>
            <a href="{{ route('bills.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Create Bill
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-check');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
    });
</script>
@endpush
@endsection
