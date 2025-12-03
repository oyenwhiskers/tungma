@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.index') }}">Bills</a>
    <i class="bi bi-chevron-right"></i>
    <span>Deleted Bills</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Deleted Bills</h2>
    <div class="text-muted">Restore previously removed bills</div>
  </div>
  <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Back to Bills
  </a>
</div>

<div class="tm-card tm-table">
  <div class="tm-card-body">
    @if($bills->isEmpty())
      <div class="text-center py-5">
        <i class="bi bi-trash" style="font-size: 48px; color: var(--tm-muted); opacity: 0.3;"></i>
        <h5 class="mt-3 mb-2">No Deleted Bills</h5>
        <p class="text-muted">All bills are currently active</p>
        <a href="{{ route('bills.index') }}" class="btn btn-primary mt-2">
          <i class="bi bi-arrow-left"></i> Back to Bills
        </a>
      </div>
    @else
      <table class="table">
        <thead>
          <tr>
            <th>Bill Code</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Company</th>
            <th>Deleted At</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
        @foreach($bills as $bill)
          <tr>
            <td><strong>{{ $bill->bill_code }}</strong></td>
            <td>{{ $bill->date?->format('M d, Y') ?? '—' }}</td>
            <td>RM {{ number_format($bill->amount, 2) }}</td>
            <td>{{ $bill->company?->name ?? '—' }}</td>
            <td>{{ $bill->deleted_at->format('M d, Y h:i A') }}</td>
            <td class="text-end">
              <form method="post" action="{{ route('bills.restore', $bill->id) }}" class="d-inline" 
                    onsubmit="return confirm('Are you sure you want to restore this bill?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                  <i class="bi bi-arrow-counterclockwise"></i> Restore
                </button>
              </form>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
      
      <div class="mt-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ $bills->firstItem() ?? 0 }} to {{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} deleted bills
        </div>
        <div>{{ $bills->links() }}</div>
      </div>
    @endif
  </div>
</div>
@endsection
