@extends('layouts.app')

@section('content')
    <div class="tm-header">
        <div>
            <h2>Activity Logs</h2>
            <div class="text-muted">Track all system activities and changes.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>

    <div class="tm-card mb-4">
        <div class="tm-card-body py-3">
            <form action="{{ route('activity-logs.index') }}" method="GET"
                class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small fw-medium text-uppercase">Filter by:</label>
                    <select name="action" class="form-select form-select-sm" style="width: 150px;"
                        onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>

                    <select name="module" class="form-select form-select-sm" style="width: 150px;"
                        onchange="this.form.submit()">
                        <option value="">All Modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                {{ $module }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(request('action') || request('module'))
                    <a href="{{ route('activity-logs.index') }}"
                        class="btn btn-link btn-sm text-danger text-decoration-none px-0">
                        <i class="bi bi-x-circle"></i> Clear filters
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="tm-card">
        <div class="tm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 200px;">User</th>
                            <th style="width: 100px;">Action</th>
                            <th style="width: 150px;">Module</th>
                            <th>Changes</th>
                            <th class="pe-4 text-end" style="width: 180px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle"
                                            style="width: 32px; height: 32px; background-color: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #4b5563;">
                                            {{ substr($log->user->name ?? 'System', 0, 1) }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-dark">{{ $log->user->name ?? 'System' }}</span>
                                            <span class="text-muted small"
                                                style="font-size: 11px;">{{ $log->ip_address }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match ($log->action) {
                                            'created' => 'bg-success-subtle text-success',
                                            'updated' => 'bg-primary-subtle text-primary',
                                            'deleted' => 'bg-danger-subtle text-danger',
                                            default => 'bg-secondary-subtle text-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill text-uppercase fw-normal"
                                        style="font-size: 10px; letter-spacing: 0.5px;">{{ $log->action }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-dark">{{ $log->model }}</span>
                                        <span class="text-muted small">#{{ $log->model_id }}</span>
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if($log->action === 'updated')
                                        <div class="d-flex flex-column gap-1">
                                            @foreach(($log->new_values ?? []) as $key => $newValue)
                                                @php
                                                    $oldValue = $log->old_values[$key] ?? null;
                                                    // Skip timestamps
                                                    if (in_array($key, ['updated_at', 'created_at']))
                                                        continue;
                                                    // Skip if no change (though logActivity logic should filter, currently it dumps all)
                                                    if ($oldValue == $newValue)
                                                        continue; 
                                                @endphp
                                                <div class="small">
                                                    <span
                                                        class="text-muted fw-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                    <span
                                                        class="text-danger text-decoration-line-through me-1">{{ is_array($oldValue) ? 'Array' : Str::limit($oldValue, 20) }}</span>
                                                    <i class="bi bi-arrow-right-short text-muted"></i>
                                                    <span
                                                        class="text-success">{{ is_array($newValue) ? 'Array' : Str::limit($newValue, 20) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($log->action === 'created')
                                        <span class="text-muted small">Created new record.</span>
                                    @elseif($log->action === 'deleted')
                                        <span class="text-muted small">Deleted record.</span>
                                    @else
                                        <span class="text-muted small">No details available.</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex flex-column">
                                        <span class="text-dark">{{ $log->created_at->format('M d, Y') }}</span>
                                        <span class="text-muted small">{{ $log->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard-data display-6 mb-3 d-block opacity-25"></i>
                                    No activities recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="p-4 border-top">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection