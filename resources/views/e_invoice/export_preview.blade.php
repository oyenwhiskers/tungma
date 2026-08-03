@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('e-invoice.index') }}">E-Invoice Requests</a>
    <i class="bi bi-chevron-right"></i>
    <span>Export Preview</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
  <div>
    <h2 class="mb-1">Unique Debtors Preview</h2>
    <div class="text-muted">A perfectly sanitized list of pending Debtors. Duplicates have been automatically removed.</div>
  </div>
</div>

<div class="tm-card tm-table mt-3">
  <div class="tm-card-header d-flex justify-content-between align-items-center">
      <div>
          <i class="bi bi-list-check me-2"></i> Debtors Ready for Export
      </div>
      <div>
          <button type="submit" form="export-form" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Download CSV & Mark as Done
          </button>
          <a href="{{ route('e-invoice.index') }}" class="btn btn-outline-secondary btn-sm ms-2">Cancel</a>
      </div>
  </div>
  <div class="tm-card-body p-0">
    <form method="POST" id="export-form" action="{{ route('e-invoice.export-tax-entity') }}">
      @csrf
      <input type="hidden" name="month" value="{{ request('month') }}">
      <input type="hidden" name="start_date" value="{{ request('start_date') }}">
      <input type="hidden" name="end_date" value="{{ request('end_date') }}">
      <input type="hidden" name="status" value="{{ request('status') }}">
      
      @if(session('error'))
          <div class="alert alert-danger m-3">{{ session('error') }}</div>
      @endif

      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="4%" class="text-center">
                  <input class="form-check-input" type="checkbox" id="checkAll" checked>
              </th>
              <th width="20%">Customer Name</th>
              <th width="20%">TIN Number</th>
              <th width="15%">Customer Type</th>
              <th width="15%">Identity No</th>
              <th width="26%">Address</th>
            </tr>
          </thead>
          <tbody>
            @forelse($uniqueDebtors as $debtor)
            <tr>
              <td class="text-center">
                  <input class="form-check-input row-check" type="checkbox" name="tins[]" value="{{ $debtor->tin_number }}" checked>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle text-muted"></i>
                    <strong>{{ $debtor->customer_name ?? 'N/A' }}</strong>
                </div>
              </td>
              <td>
                <span class="fw-bold text-primary">{{ $debtor->tin_number ?? 'N/A' }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border px-2 py-1 fs-6">{{ $debtor->customer_type ?? 'Individual' }}</span>
              </td>
              <td>
                {{ $debtor->customer_ic ?: $debtor->business_reg_number }}
              </td>
              <td class="small text-muted text-truncate" style="max-width: 250px;">
                {{ is_array($debtor->address) ? implode(', ', $debtor->address) : $debtor->address }}, {{ $debtor->postcode }} {{ $debtor->city }}
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                <div class="title">You are all caught up!</div>
                <p>No new unique Debtors pending.</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      
      @if($uniqueDebtors && count($uniqueDebtors) > 0)
      <div class="p-3 bg-light border-top text-muted small">
          <i class="bi bi-info-circle text-primary"></i> Submitting this will mark all associated bills as Handled.
      </div>
      @endif
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-check');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endpush
