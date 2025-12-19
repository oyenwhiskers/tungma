@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('backup.index') }}">Backup & Restore</a>
    <i class="bi bi-chevron-right"></i>
    <span>Delete Bills</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Delete Database Records</h2>
        <div class="text-muted">Select bills to delete (Gmail-style selection)</div>
    </div>
    <div>
        <a href="{{ route('backup.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Backup
        </a>
    </div>
</div>

{{-- Alert Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Filters --}}
<div class="tm-card">
    <div class="tm-card-header">
        <i class="bi bi-funnel me-2"></i> Filters
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('backup.delete.bills') }}" class="row g-3">
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

            <div class="col-md-2">
                <label class="form-label small text-muted">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small text-muted">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="{{ route('backup.delete.bills') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Selection Toolbar --}}
<div class="tm-card mt-3" id="selectionToolbar" style="display: none;">
    <div class="tm-card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span id="selectedCount" class="fw-bold">0</span> bill(s) selected
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="clearSelectionBtn">
                    <i class="bi bi-x-circle"></i> Clear Selection
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="deleteSelectedBtn" data-bs-toggle="modal" data-bs-target="#deleteSelectedModal">
                    <i class="bi bi-trash"></i> Delete Selected
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Bills Table --}}
<div class="tm-card tm-table mt-3">
    <div class="tm-card-body">
        @if($bills->count() > 0)
        <form id="billsForm">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Bill Code</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Payment Status</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $bill)
                    <tr>
                        <td>
                            <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" 
                                   class="form-check-input bill-checkbox">
                        </td>
                        <td><strong>{{ $bill->bill_code }}</strong></td>
                        <td>{{ $bill->date?->format('M d, Y') ?? '—' }}</td>
                        <td>RM {{ number_format($bill->amount, 2) }}</td>
                        <td>{{ $bill->company?->name ?? '—' }}</td>
                        <td>
                            @if($bill->customer_info && is_array($bill->customer_info))
                                {{ $bill->customer_info['name'] ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($bill->is_paid)
                                <span class="badge bg-success">Paid</span>
                            @else
                                <span class="badge bg-warning">Unpaid</span>
                            @endif
                        </td>
                        <td>{{ $bill->creator?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
            <p>Try adjusting your filters</p>
        </div>
        @endif
    </div>
</div>

{{-- Delete Selected Modal --}}
<div class="modal fade" id="deleteSelectedModal" tabindex="-1" aria-labelledby="deleteSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSelectedModalLabel">Confirm Delete Selected Bills</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteSelectedForm" method="POST" action="{{ route('backup.delete.selected.bills') }}">
                @csrf
                <div id="selectedBillsInputs"></div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    <div class="mb-3">
                        <p>You are about to delete <strong id="modalSelectedCount">0</strong> selected bill(s).</p>
                        <p class="text-muted small mb-0">All selected bills will be permanently deleted from the database.</p>
                    </div>
                    <p class="mb-3">Please enter your password to confirm this action:</p>
                    <div class="mb-3">
                        <label for="modalDeletePassword" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="modalDeletePassword" name="password" required autocomplete="current-password" autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const billCheckboxes = document.querySelectorAll('.bill-checkbox');
    const selectionToolbar = document.getElementById('selectionToolbar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const deleteSelectedModal = document.getElementById('deleteSelectedModal');
    const deleteSelectedForm = document.getElementById('deleteSelectedForm');
    const selectedBillsInputs = document.getElementById('selectedBillsInputs');
    const modalSelectedCount = document.getElementById('modalSelectedCount');
    const modalDeletePassword = document.getElementById('modalDeletePassword');

    function updateSelection() {
        const selected = document.querySelectorAll('.bill-checkbox:checked');
        const count = selected.length;
        
        selectedCountSpan.textContent = count;
        modalSelectedCount.textContent = count;
        
        if (count > 0) {
            selectionToolbar.style.display = 'block';
            selectAllCheckbox.indeterminate = count > 0 && count < billCheckboxes.length;
            selectAllCheckbox.checked = count === billCheckboxes.length;
        } else {
            selectionToolbar.style.display = 'none';
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        }
    }

    // Select all functionality
    selectAllCheckbox.addEventListener('change', function() {
        billCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelection();
    });

    // Individual checkbox change
    billCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });

    // Clear selection
    clearSelectionBtn.addEventListener('click', function() {
        billCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        updateSelection();
    });

    // Handle modal show
    deleteSelectedModal.addEventListener('show.bs.modal', function() {
        const selected = document.querySelectorAll('.bill-checkbox:checked');
        const selectedIds = Array.from(selected).map(cb => cb.value);
        
        // Clear previous inputs
        selectedBillsInputs.innerHTML = '';
        
        // Add hidden inputs for selected bill IDs
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'bill_ids[]';
            input.value = id;
            selectedBillsInputs.appendChild(input);
        });
        
        modalDeletePassword.value = '';
        modalDeletePassword.classList.remove('is-invalid');
    });

    // Clear password field when modal is hidden
    deleteSelectedModal.addEventListener('hidden.bs.modal', function() {
        modalDeletePassword.value = '';
        modalDeletePassword.classList.remove('is-invalid');
    });

    // Check if there's a password error and show modal
    @if($errors->has('password') && old('bill_ids'))
        const savedBillIds = @json(old('bill_ids'));
        if (savedBillIds && savedBillIds.length > 0) {
            // Restore selections
            savedBillIds.forEach(id => {
                const checkbox = document.querySelector(`.bill-checkbox[value="${id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updateSelection();
            
            // Show modal
            modalDeletePassword.classList.add('is-invalid');
            const bsModal = new bootstrap.Modal(deleteSelectedModal);
            bsModal.show();
        }
    @endif

    // Initial update
    updateSelection();
});
</script>
@endsection



