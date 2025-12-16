@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Checklists</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Daily Checklists</h2>
        <div class="text-muted">
            Manage bus departure checklists for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
        </div>
    </div>
    <div>
        <form method="GET" action="{{ route('checklists.index') }}" class="d-flex gap-2">
            <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ now()->toDateString() }}">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
</div>

    <div class="row">
        <div class="col-12">
            <div class="tm-card">
                <div class="tm-card-header">
                    <i class="bi bi-list-check me-2" style="color:var(--tm-primary);"></i>
                    Departures
                </div>
                <div class="tm-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Departure Time</th>
                                    <th>Status</th>
                                    <th>Checked By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row['bus_datetime'])->format('H:i') }}</td>
                                        <td>
                                            @if($row['status'] === 'success')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($row['status'] === 'pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @else
                                                <span class="badge bg-secondary">No Data</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['checked_by'] }}</td>
                                        <td class="text-end">
                                            @if($row['status'] !== 'no data')
                                                <a href="{{ route('checklists.show', ['bus_datetime' => $row['bus_datetime']]) }}"
                                                    class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                            @else
                                                <button class="btn btn-sm btn-secondary" disabled>View</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No departures found for today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection