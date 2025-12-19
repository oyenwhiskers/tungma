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

    <div class="tm-card border-0 shadow-sm">
        <div class="tm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-top mb-0" style="table-layout: fixed;">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted fw-bold text-uppercase" style="width: 220px; font-size: 11px; letter-spacing: 0.5px;">User</th>
                            <th class="py-3 text-muted fw-bold text-uppercase" style="width: 120px; font-size: 11px; letter-spacing: 0.5px;">Context</th>
                            <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Changes</th>
                            <th class="pe-4 py-3 text-end text-muted fw-bold text-uppercase" style="width: 150px; font-size: 11px; letter-spacing: 0.5px;">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle shadow-sm"
                                            style="width: 36px; height: 36px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4b5563; border: 2px solid white;">
                                            <span style="font-size: 13px; font-weight: 700;">{{ substr($log->user->name ?? 'S', 0, 1) }}</span>
                                        </div>
                                        <div class="d-flex flex-column" style="line-height: 1.3;">
                                            <span class="fw-semibold text-dark" style="font-size: 14px;">{{ $log->user->name ?? 'System' }}</span>
                                            <span class="text-muted" style="font-size: 11px;">{{ $log->ip_address }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex flex-column gap-1">
                                        @php
                                            $actionColor = match ($log->action) {
                                                'created' => 'success',
                                                'updated' => 'primary',
                                                'deleted' => 'danger',
                                                default => 'secondary',
                                            };
                                            $actionIcon = match ($log->action) {
                                                'created' => 'bi-plus-lg',
                                                'updated' => 'bi-pencil',
                                                'deleted' => 'bi-trash',
                                                default => 'bi-circle',
                                            };
                                            $moduleIcon = match ($log->model) {
                                                'User' => 'bi-person',
                                                'Bill' => 'bi-receipt',
                                                'Company' => 'bi-building',
                                                'BusDepartures' => 'bi-bus-front',
                                                'CourierPolicy' => 'bi-file-text',
                                                default => 'bi-box',
                                            };
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-{{ $actionColor }}-subtle text-{{ $actionColor }} border border-{{ $actionColor }}-subtle rounded-pill d-inline-flex align-items-center gap-1 px-2 py-1" style="font-weight: 600; font-size: 10px;">
                                                <i class="{{ $actionIcon }}"></i> {{ ucfirst($log->action) }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center text-muted small" style="font-size: 12px;">
                                            <i class="{{ $moduleIcon }} me-1 opacity-75"></i>
                                            <span class="fw-medium">{{ $log->model }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="bg-light rounded-3 p-3 border border-light-subtle">
                                        @if($log->action === 'updated')
                                            <div class="d-flex flex-column gap-2">
                                                @foreach(($log->new_values ?? []) as $key => $newValue)
                                                    @php
                                                        $oldValue = $log->old_values[$key] ?? null;
                                                        if (in_array($key, ['updated_at', 'created_at'])) continue;
                                                        if ($oldValue == $newValue) continue; 
                                                    @endphp
                                                    <div class="d-flex flex-column flex-sm-row gap-1 gap-sm-2 text-break" style="font-size: 13px;">
                                                        <span class="fw-semibold text-secondary min-w-100" style="min-width: 120px;">
                                                            {{ ucwords(str_replace('_', ' ', $key)) }}
                                                        </span>
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span class="text-danger bg-danger-subtle px-1 rounded text-decoration-line-through opacity-75 small">
                                                                {{ is_array($oldValue) ? 'Array' : Str::limit($oldValue, 40) }}
                                                            </span>
                                                            <i class="bi bi-arrow-right-short text-muted"></i>
                                                            <span class="text-success fw-medium">
                                                                {{ is_array($newValue) ? 'Array' : Str::limit($newValue, 40) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($log->action === 'created')
                                            <div class="row g-2">
                                                @foreach(($log->new_values ?? []) as $key => $value)
                                                    @if(in_array($key, ['created_at', 'updated_at', 'deleted_at', 'id'])) @continue @endif
                                                    @if(empty($value)) @continue @endif
                                                    <div class="col-6 col-md-4" style="font-size: 12px;">
                                                        <span class="text-muted d-block small">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                        <span class="text-dark fw-medium text-break">{{ is_array($value) ? 'Array' : Str::limit((string)$value, 50) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($log->action === 'deleted')
                                            <div class="row g-2">
                                                <div class="col-12 mb-1">
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 10px;">Deleted Data Snapshot</span>
                                                </div>
                                                @foreach(($log->old_values ?? []) as $key => $value)
                                                    @if(in_array($key, ['created_at', 'updated_at', 'deleted_at', 'id'])) @continue @endif
                                                    @if(empty($value)) @continue @endif
                                                    <div class="col-6 col-md-4" style="font-size: 12px;">
                                                        <span class="text-muted d-block small">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                                        <span class="text-secondary text-decoration-line-through text-break">{{ is_array($value) ? 'Array' : Str::limit((string)$value, 50) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted small fst-italic">No detailed changes recorded.</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold text-dark" style="font-size: 13px;">{{ $log->created_at->format('M d, Y') }}</span>
                                        <span class="text-muted" style="font-size: 11px;">{{ $log->created_at->format('h:i A') }}</span>
                                        <span class="text-muted small mt-1" style="font-size: 10px;">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="bg-light rounded-circle p-4 mb-3">
                                            <i class="bi bi-clipboard-data text-muted opacity-50 display-6"></i>
                                        </div>
                                        <h5 class="text-muted fw-medium fs-6">No activities found</h5>
                                        <p class="text-muted small mb-0">Try adjusting your filters or come back later.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection