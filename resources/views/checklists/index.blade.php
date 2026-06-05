@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Checklists</span>
</div>

<div class="tm-header mb-3">
    <div>
        <h2 class="mb-1">Daily Checklists</h2>
        <div class="text-muted">
            Manage bus departure checklists for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
        </div>
    </div>
</div>

<div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}" class="btn btn-sm {{ $type === 'all' || !in_array($type, ['ongoing', 'ingoing']) ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-list-task"></i> All Checklists
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'ongoing']) }}" class="btn btn-sm {{ $type === 'ongoing' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-arrow-up-right-circle"></i> Ongoing (Handover)
        </a>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'ingoing']) }}" class="btn btn-sm {{ $type === 'ingoing' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-arrow-down-left-circle"></i> Ingoing (Arrival)
        </a>
    </div>
    
    <form method="GET" action="{{ route('checklists.index') }}" class="d-flex gap-2">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" max="{{ now()->toDateString() }}" onchange="this.form.submit()">
        <button type="submit" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-search"></i>
        </button>
    </form>
</div>

    <div class="row">
        <div class="col-12">
            <div class="tm-card">
                <div class="tm-card-header">
                    <i class="bi bi-list-check me-2" style="color:var(--tm-primary);"></i>
                    Departures / Handover Checklists
                </div>
                <div class="tm-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Departure Time</th>
                                    <th>Direction</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Checked By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td>
                                            <strong class="text-dark">{{ $row['departure_time'] ? \Carbon\Carbon::parse($row['departure_time'])->format('h:i A') : '—' }}</strong>
                                        </td>
                                        <td>
                                            @if($row['direction'] === 'Ongoing (Outgoing)')
                                                <span class="text-danger fw-semibold"><i class="bi bi-arrow-up-right-circle"></i> Ongoing (Outgoing)</span>
                                            @elseif($row['direction'] === 'Ingoing (Incoming)')
                                                <span class="text-success fw-semibold"><i class="bi bi-arrow-down-left-circle"></i> Ingoing (Incoming)</span>
                                            @else
                                                <span class="text-primary fw-semibold"><i class="bi bi-arrow-left-right"></i> Both Directions</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="small fw-semibold text-muted">{{ $row['checked_count'] }}/{{ $row['total'] }}</span>
                                                <div class="progress" style="width: 70px; height: 6px;">
                                                    <div class="progress-bar {{ $row['checked_count'] === $row['total'] ? 'bg-success' : 'bg-warning' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ ($row['checked_count'] / ($row['total'] ?: 1)) * 100 }}%"></div>
                                                </div>
                                            </div>
                                        </td>
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
                                            @if($row['status'] !== 'no data' && $row['bus_departures_id'])
                                                <a href="{{ route('checklists.show', ['bus_departures_id' => $row['bus_departures_id'], 'date' => $row['date'], 'type' => $type]) }}"
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