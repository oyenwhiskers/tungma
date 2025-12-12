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
                    <option value="credit_card" {{ request('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                    <option value="e_wallet" {{ request('payment_method') == 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small text-muted">Date </label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            
            <div class="col-md-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                    @if(request()->hasAny(['search', 'payment_status', 'company_id', 'payment_method', 'date_from', 'date_to']))
                        <span class="align-self-center text-muted small ms-2">
                            <i class="bi bi-info-circle"></i> {{ $bills->total() }} result(s) found
                        </span>
                    @endif
                </div>
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
                    <th>Bill Code</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Company</th>
                    <th>ETA</th>
                    <th>Payment Type</th>
                    <th>Payment Status</th>
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
                    <td>
                        @php
                            $payment = $bill->payment_details ? json_decode($bill->payment_details, true) : null;
                        @endphp
                        {{ $payment['method'] ?? '—' }}
                    </td>
                    <td>{{ $bill->is_paid ? 'Paid' : 'Unpaid' }}</td>
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
