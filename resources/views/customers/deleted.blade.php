@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('customers.index') }}">Customers List</a>
    <i class="bi bi-chevron-right"></i>
    <span>Deleted Customers</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
    <div>
        <h2 class="mb-1 text-danger">Deleted Customers</h2>
        <div class="text-muted">Restore accidentally deleted buyer profiles.</div>
    </div>
    <div>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Active Customers
        </a>
    </div>
</div>

<div class="tm-card">
    <div class="tm-card-header text-danger">
        <i class="bi bi-search me-2"></i> Search Deleted Customers
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('customers.deleted') }}" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search deleted name, contact, or debtor code..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-outline-danger">Search</button>
                <a href="{{ route('customers.deleted') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="tm-card tm-table mt-3">
    <div class="tm-card-body">
        @if($customers->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Name / Company Name</th>
                    <th>Contact Number</th>
                    <th>Debtor Code</th>
                    <th>Deleted At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <i class="bi bi-person-x"></i>
                            <strong>{{ $customer->name }}</strong>
                        </div>
                    </td>
                    <td class="text-muted">{{ $customer->contact_number ?: '—' }}</td>
                    <td>
                        <span class="badge bg-light text-muted border px-2 py-1 fs-6">
                            {{ $customer->debtor_code }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $customer->deleted_at->format('M d, Y h:i A') }}</td>
                    <td class="text-end">
                        <form action="{{ route('customers.restore', $customer->id) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to restore this customer?');">
                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted" style="font-size:13px;">
                Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} deleted customers
            </div>
            <div>{{ $customers->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-trash"></i>
            <div class="title">No deleted customers</div>
            <p>There are no deleted customers to restore.</p>
        </div>
        @endif
    </div>
</div>
@endsection
