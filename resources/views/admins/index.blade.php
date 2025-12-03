@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Admins</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Admins</h2>
    <div class="text-muted">Manage administrator accounts and permissions</div>
  </div>
  <div>
    <a href="{{ route('admins.deleted') }}" class="btn btn-outline-secondary me-2">
        <i class="bi bi-trash"></i> Deleted Admins
    </a>
    <a href="{{ route('admins.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> New Admin
    </a>
  </div>
</div>

<div class="tm-card tm-table">
  <div class="tm-card-body">
    @if($admins->count() > 0)
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
      @foreach($admins as $admin)
        <tr>
          <td>
            <a href="{{ route('admins.show', $admin) }}" class="d-flex align-items-center gap-2">
              <i class="bi bi-person-badge"></i>
              <strong>{{ $admin->name }}</strong>
            </a>
          </td>
          <td>{{ $admin->username ?? '—' }}</td>
          <td>{{ $admin->email }}</td>
          <td>{{ $admin->position ?? '—' }}</td>
          <td>{{ $admin->company?->name ?? '—' }}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <a href="{{ route('admins.show', $admin) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye"></i>
              </a>
              <a href="{{ route('admins.edit', $admin) }}" class="btn btn-outline-secondary">
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
            Showing {{ $admins->firstItem() ?? 0 }} to {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} admins
        </div>
        <div>{{ $admins->links() }}</div>
    </div>
    @else
    <div class="tm-empty-state">
        <i class="bi bi-person-badge"></i>
        <div class="title">No admins found</div>
        <p>Create your first administrator account</p>
        <a href="{{ route('admins.create') }}" class="btn btn-primary mt-2">
            <i class="bi bi-plus-circle"></i> Create Admin
        </a>
    </div>
    @endif
  </div>
</div>
@endsection
