@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Customers List</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
    <div>
        <h2 class="mb-1">Registered Customers / Companies</h2>
        <div class="text-muted">Manage buyer profiles and their unique debtor codes.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.deleted') }}" class="btn btn-outline-secondary">
            <i class="bi bi-trash"></i> Deleted Customers
        </a>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Customer
        </a>
    </div>
</div>

<div class="tm-card">
    <div class="tm-card-header">
        <i class="bi bi-search me-2"></i> Search Customers
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name, contact, or debtor code..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Clear</a>
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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-muted"></i>
                            <strong>{{ $customer->name }}</strong>
                        </div>
                    </td>
                    <td>{{ $customer->contact_number ?: '—' }}</td>
                    <td>
                        <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                            {{ $customer->debtor_code }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted" style="font-size:13px;">
                Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers
            </div>
            <div>{{ $customers->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-people"></i>
            <div class="title">No customers found</div>
            <p>Register your first customer to get started</p>
            <a href="{{ route('customers.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Add Customer
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
