@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('staff.index') }}">Staff</a>
    <i class="bi bi-chevron-right"></i>
    <span>Deleted</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Deleted Staff</h2>
    <div class="text-muted">Restore previously removed staff members to active status</div>
  </div>
  <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Back to Staff
  </a>
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
          <th>Company</th>
          <th>Deleted At</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      @foreach($staff as $user)
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-person text-muted"></i>
              <strong>{{ $user->name }}</strong>
            </div>
          </td>
          <td>{{ $user->username ?? '—' }}</td>
          <td>{{ $user->email }}</td>
          <td>{{ $user->company?->name ?? '—' }}</td>
          <td>
            <span class="text-muted small">
              <i class="bi bi-clock"></i> {{ $user->deleted_at->format('M d, Y h:i A') }}
            </span>
          </td>
          <td class="text-end">
            <form method="post" action="{{ route('staff.restore', $user->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this staff member?');">
              @csrf
              <button type="submit" class="btn btn-sm btn-success">
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
            Showing {{ $staff->firstItem() ?? 0 }} to {{ $staff->lastItem() ?? 0 }} of {{ $staff->total() }} deleted staff members
        </div>
        <div>{{ $staff->links() }}</div>
    </div>
    @else
    <div class="tm-empty-state">
        <i class="bi bi-trash"></i>
        <div class="title">No deleted staff members</div>
        <p>All staff members are currently active</p>
        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary mt-2">
            <i class="bi bi-arrow-left"></i> Back to Staff
        </a>
    </div>
    @endif
  </div>
</div>
@endsection
