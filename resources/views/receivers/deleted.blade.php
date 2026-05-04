@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('receivers.index') }}">Receivers</a>
    <i class="bi bi-chevron-right"></i>
    <span>Deleted</span>
</div>

<div class="tm-header d-flex justify-content-between align-items-center">
    <div>
        <h2 class="mb-1">Deleted Receivers</h2>
        <div class="text-muted">Restore previously deleted receivers.</div>
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
                    <th>Deleted At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receivers as $receiver)
                <tr>
                    <td>{{ $receiver->name }}</td>
                    <td>{{ $receiver->contact_number ?: '—' }}</td>
                    <td>{{ $receiver->deleted_at->format('M d, Y h:i A') }}</td>
                    <td class="text-end">
                        <form action="{{ route('receivers.restore', $receiver->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="mt-3">
            {{ $receivers->links() }}
        </div>
        @else
        <div class="tm-empty-state">
            <i class="bi bi-trash"></i>
            <div class="title">No deleted receivers</div>
            <p>Deleted receivers will appear here for restoration</p>
        </div>
        @endif
    </div>
</div>
@endsection
