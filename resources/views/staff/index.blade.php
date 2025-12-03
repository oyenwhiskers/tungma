@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Staff</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Staff</h2>
    <div class="text-muted">Manage staff members and their assignments</div>
  </div>
  <div>
    <a href="{{ route('staff.deleted') }}" class="btn btn-outline-secondary me-2">
        <i class="bi bi-trash"></i> Deleted Staff
    </a>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> New Staff
    </a>
  </div>
</div>

<div class="tm-card tm-table">
  <div class="tm-card-body">
    @if($staff->count() > 0)
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Position</th>
          <th>Company</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      @foreach($staff as $user)
        <tr>
          <td>
            <a href="{{ route('staff.show', $user) }}" class="d-flex align-items-center gap-2">
              <i class="bi bi-person"></i>
              <strong>{{ $user->name }}</strong>
            </a>
          </td>
          <td>{{ $user->username ?? '—' }}</td>
          <td>{{ $user->email }}</td>
          <td>{{ $user->position ?? '—' }}</td>
          <td>{{ $user->company?->name ?? '—' }}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <a href="{{ route('staff.show', $user) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('staff.edit', $user) }}" class="btn btn-outline-secondary">
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
            Showing {{ $staff->firstItem() ?? 0 }} to {{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} staff members
        </div>
        <div>{{ $staff->links() }}</div>
    </div>
    @else
    <div class="tm-empty-state">
        <i class="bi bi-people"></i>
        <div class="title">No staff members found</div>
        <p>Create your first staff account</p>
        <a href="{{ route('staff.create') }}" class="btn btn-primary mt-2">
            <i class="bi bi-plus-circle"></i> Create Staff
        </a>
    </div>
    @endif
  </div>
</div>
@endsection
