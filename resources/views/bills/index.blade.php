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

<div class="tm-card tm-table">
    <div class="tm-card-body">
        @if($bills->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Bill Code</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Company</th>
                    <th>ETA</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($bills as $bill)
                <tr>
                    <td>
                        <a href="{{ route('bills.show', $bill) }}" class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt"></i>
                            <strong>{{ $bill->bill_code }}</strong>
                        </a>
                    </td>
                    <td>{{ $bill->date?->format('M d, Y') ?? '—' }}</td>
                    <td><strong>RM {{ number_format($bill->amount, 2) }}</strong></td>
                    <td>{{ $bill->company?->name ?? '—' }}</td>
                    <td>{{ $bill->eta ?? '—' }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('bills.edit', $bill) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
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
@endsection
