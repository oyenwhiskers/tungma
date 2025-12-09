@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.index') }}">Bills</a>
    <i class="bi bi-chevron-right"></i>
    <span>{{ $bill->bill_code }}</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Bill {{ $bill->bill_code }}</h2>
    <div class="text-muted">Complete bill details and information</div>
  </div>
  <div>
    <a href="{{ route('bills.edit', $bill) }}" class="btn btn-primary me-2">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-receipt me-2"></i> Bill Information
            </div>
            <div class="tm-card-body p-0">
                <div class="company-info-grid">
                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-hash"></i>
                      <span>Bill ID/Code</span>
                    </div>
                    <div class="info-value">
                      <strong>{{ $bill->bill_code }}</strong>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-calendar"></i>
                      <span>Bill Date</span>
                    </div>
                    <div class="info-value">
                      {{ $bill->date?->format('M d, Y') ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-cash-stack"></i>
                      <span>Bill Amount</span>
                    </div>
                    <div class="info-value">
                      <strong style="font-size:18px;">RM {{ number_format($bill->amount, 2) }}</strong>
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-file-text"></i>
                      <span>Description</span>
                    </div>
                    <div class="info-value">
                      {{ $bill->description ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-credit-card"></i>
                      <span>Payment Method</span>
                    </div>
                    <div class="info-value">
                      @if($bill->payment_details)
                        @php $payment = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details; @endphp
                        {{ $payment['method'] ?? '—' }}
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-calendar-check"></i>
                      <span>Payment Date</span>
                    </div>
                    <div class="info-value">
                      @if($bill->payment_details)
                        @php $payment = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details; @endphp
                        {{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('M d, Y') : '—' }}
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-person"></i>
                      <span>Customer Information</span>
                    </div>
                    <div class="info-value">
                      @if($bill->customer_info)
                        @php $customer = is_string($bill->customer_info) ? json_decode($bill->customer_info, true) : $bill->customer_info; @endphp
                        <div>
                          <div><strong>{{ $customer['name'] ?? '—' }}</strong></div>
                          @if(isset($customer['phone']))<div class="small text-muted">{{ $customer['phone'] }}</div>@endif
                          @if(isset($customer['address']))<div class="small text-muted">{{ $customer['address'] }}</div>@endif
                        </div>
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-file-earmark-text"></i>
                      <span>Courier Policy</span>
                    </div>
                    <div class="info-value">
                      @php $ps = $bill->policy_snapshot; @endphp
                      @if($ps && isset($ps['name']))
                        <div>
                          <div><strong>{{ $ps['name'] }}</strong></div>
                          @if(!empty($ps['description']))
                            <div class="small text-muted">{{ $ps['description'] }}</div>
                          @endif
                          @if(isset($ps['company_name']))
                            <div class="small">Company: {{ $ps['company_name'] }}</div>
                          @endif
                        </div>
                      @elseif($bill->courierPolicy)
                        <a href="{{ route('policies.show', $bill->courierPolicy) }}">{{ $bill->courierPolicy->name }}</a>
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-building"></i>
                      <span>Company</span>
                    </div>
                    <div class="info-value">
                      @if($bill->company)
                        <a href="{{ route('companies.show', $bill->company) }}">{{ $bill->company->name }}</a>
                      @else
                        —
                      @endif
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-truck"></i>
                      <span>ETA (Estimated Arrival)</span>
                    </div>
                    <div class="info-value">
                      {{ $bill->eta ?? '—' }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-receipt-cutoff"></i>
                      <span>SST Details</span>
                    </div>
                    <div class="info-value">
                      @if($bill->sst_details)
                        @php $sst = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details; @endphp
                        <div>
                          @if(isset($sst['rate']))<div>Rate: {{ $sst['rate'] }}%</div>@endif
                          @if(isset($sst['amount']))<div>Amount: RM {{ number_format($sst['amount'], 2) }}</div>@endif
                        </div>
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
                      {{ $bill->created_at->format('M d, Y h:i A') }}
                    </div>
                  </div>

                  <div class="info-row">
                    <div class="info-label">
                      <i class="bi bi-clock-history"></i>
                      <span>Last Updated</span>
                    </div>
                    <div class="info-value">
                      {{ $bill->updated_at->format('M d, Y h:i A') }}
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-info-circle me-2"></i> Summary
            </div>
            <div class="tm-card-body">
                <div class="mb-3 pb-3 border-bottom">
                    <div class="text-muted small mb-1">Status</div>
                    <span class="badge bg-success">Active</span>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <div class="text-muted small mb-1">Total Amount</div>
                    <div class="h4 mb-0 text-primary">RM {{ number_format($bill->amount, 2) }}</div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Attachments</div>
                    <div class="small">No attachments</div>
                </div>
            </div>
        </div>

        <div class="tm-card mt-3">
            <div class="tm-card-header">
                <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
            </div>
            <div class="tm-card-body">
                <div class="d-grid gap-2">
                    <form method="post" action="{{ route('bills.destroy', $bill) }}" onsubmit="return confirm('Are you sure you want to delete this bill?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 text-start">
                            <i class="bi bi-trash"></i> Delete Bill
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
    grid-template-columns: 220px 1fr;
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
