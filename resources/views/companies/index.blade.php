@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Companies</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Companies</h2>
        <div class="text-muted">Manage your company records and partnerships</div>
    </div>
    <div>
        <a href="{{ route('companies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Company
        </a>
    </div>
</div>

<div class="tm-card tm-table">
    <div class="tm-card-body">
        @if($companies->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($companies as $company)
                <tr>
                    <td>
                        <a href="{{ route('companies.show', $company) }}" class="d-flex align-items-center gap-2">
                            <i class="bi bi-building"></i>
                            <strong>{{ $company->name }}</strong>
                        </a>
                    </td>
                    <td>{{ $company->contact_number ?? '—' }}</td>
                    <td>{{ $company->email ?? '—' }}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('companies.show', $company) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-outline-secondary">
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
                Showing {{ $companies->firstItem() ?? 0 }} to {{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }} companies
            </div>
            <div>{{ $companies->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-building"></i>
            <div class="title">No companies found</div>
            <p>Get started by creating your first company</p>
            <a href="{{ route('companies.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Create Company
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
