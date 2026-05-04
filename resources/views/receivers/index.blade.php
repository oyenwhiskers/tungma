@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Receivers List</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
    <div>
        <h2 class="mb-1">Preset Receivers</h2>
        <div class="text-muted">Manage regular receivers for quick bill creation.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('receivers.deleted') }}" class="btn btn-outline-secondary">
            <i class="bi bi-trash"></i> Deleted Receivers
        </a>
        <a href="{{ route('receivers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Receiver
        </a>
    </div>
</div>

<div class="tm-card">
    <div class="tm-card-header">
        <i class="bi bi-search me-2"></i> Search Receivers
    </div>
    <div class="tm-card-body">
        <form method="GET" action="{{ route('receivers.index') }}" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search name or contact..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('receivers.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="tm-card tm-table mt-3">
    <div class="tm-card-body">
        @if($receivers->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact Number</th>
                    <th>Assigned Company</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receivers as $receiver)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-muted"></i>
                            <strong>{{ $receiver->name }}</strong>
                        </div>
                    </td>
                    <td>{{ $receiver->contact_number ?: '—' }}</td>
                    <td>
                        @if($receiver->company)
                            <span class="badge bg-light text-dark border px-2 py-1">
                                {{ $receiver->company->name }}
                            </span>
                        @else
                            <span class="text-muted">Global</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('receivers.edit', $receiver) }}" class="btn btn-outline-secondary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('receivers.destroy', $receiver) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this receiver?');">
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
                Showing {{ $receivers->firstItem() ?? 0 }} to {{ $receivers->lastItem() ?? 0 }} of {{ $receivers->total() }} receivers
            </div>
            <div>{{ $receivers->links() }}</div>
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-people"></i>
            <div class="title">No receivers found</div>
            <p>Register your first receiver to get started</p>
            <a href="{{ route('receivers.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Add Receiver
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
