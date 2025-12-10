@extends('layouts.app')

@section('content')
<div class="tm-header">
    <div>
        <h2 class="mb-1">Backup & Restore</h2>
        <div class="text-muted">Export and import bills data and media files</div>
    </div>
</div>

{{-- Alert Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Storage Metrics --}}
<div class="row g-3">
    <div class="col-12">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-hdd me-2"></i> Storage Metrics
            </div>
            <div class="tm-card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Storage Area</th>
                                <th>Size (MB)</th>
                                <th>Path</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Backups</strong></td>
                                <td>{{ number_format($metrics['backups'] / 1048576, 2) }}</td>
                                <td><small class="text-muted">storage/app/backups</small></td>
                                <td>
                                    <form method="POST" action="{{ route('backup.clear.storage') }}" class="d-inline"
                                          onsubmit="return confirm('Clear all backups? This cannot be undone!')">
                                        @csrf
                                        <input type="hidden" name="target" value="backups">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Clear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Media Files</strong></td>
                                <td>{{ number_format($metrics['media'] / 1048576, 2) }}</td>
                                <td><small class="text-muted">storage/app/public</small></td>
                                <td>
                                    <form method="POST" action="{{ route('backup.clear.storage') }}" class="d-inline"
                                          onsubmit="return confirm('Clear all media files? This will delete bills attachments and other uploads!')">
                                        @csrf
                                        <input type="hidden" name="target" value="media">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Clear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Logs</strong></td>
                                <td>{{ number_format($metrics['logs'] / 1048576, 2) }}</td>
                                <td><small class="text-muted">storage/logs</small></td>
                                <td>
                                    <form method="POST" action="{{ route('backup.clear.storage') }}" class="d-inline"
                                          onsubmit="return confirm('Clear all log files?')">
                                        @csrf
                                        <input type="hidden" name="target" value="logs">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> Clear
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Complete Backup/Restore --}}
<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-archive me-2"></i> Complete Backup
            </div>
            <div class="tm-card-body">
                <p class="text-muted mb-3">Backup data in one ZIP file</p>

                <form action="{{ route('backup.export.all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-download"></i> Backup Everything
                    </button>
                    <small class="text-muted d-block mt-2">
                        Creates one ZIP with bills data
                    </small>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-arrow-clockwise me-2"></i> Complete Restore
            </div>
            <div class="tm-card-body">
                <p class="text-muted mb-3">Restore everything from one ZIP file</p>

                <form action="{{ route('backup.import.all') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" class="form-control @error('complete_file') is-invalid @enderror"
                               id="complete_file" name="complete_file" accept=".zip" required>
                        @error('complete_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="backup_existing" id="backup_existing_all">
                        <label class="form-check-label" for="backup_existing_all">
                            Backup existing media before restore
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-upload"></i> Restore Everything
                    </button>
                    <small class="text-muted d-block mt-2">
                        Restores bills data from backup ZIP
                    </small>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Advanced: Individual Backup/Restore --}}
<div class="row g-3 mt-2">
    <div class="col-12">
        <details class="tm-card">
            <summary class="tm-card-header" style="cursor: pointer;">
                <i class="bi bi-sliders me-2"></i> Advanced: Individual Backup/Restore
            </summary>
            <div class="tm-card-body">
                <div class="row g-3">
                    {{-- Export Section --}}
                    <div class="col-md-6">
                        <h6 class="mb-2">Export / Backup</h6>
                        {{-- Export Data Button --}}
                        <form action="{{ route('backup.export.data') }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-file-earmark-text"></i> Data Only (JSON)
                            </button>
                        </form>

                        {{-- Export Media Button --}}
                        <form action="{{ route('backup.export.media') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-file-earmark-zip"></i> Media Only (ZIP)
                            </button>
                        </form>
                    </div>

                    {{-- Import Section --}}
                    <div class="col-md-6">
                        <h6 class="mb-2">Import / Restore</h6>
                        {{-- Import Data Form --}}
                        <form action="{{ route('backup.import.data') }}" method="POST" enctype="multipart/form-data" class="mb-2">
                            @csrf
                            <input type="file" class="form-control form-control-sm mb-1"
                                   id="data_file" name="data_file" accept=".json,.txt" required>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-upload"></i> Restore Data (JSON)
                            </button>
                        </form>

                        {{-- Import Media Form --}}
                        <form action="{{ route('backup.import.media') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" class="form-control form-control-sm mb-1"
                                   id="media_file" name="media_file" accept=".zip" required>
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-upload"></i> Restore Media (ZIP)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>

{{-- Existing Backups List --}}
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-folder2-open me-2"></i> Existing Backups
            </div>
            <div class="tm-card-body">
                @if(count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                    <tr>
                                        <td><small>{{ $backup['filename'] }}</small></td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $backup['type'] }}</span>
                                        </td>
                                        <td>{{ $backup['size_human'] }}</td>
                                        <td>{{ $backup['date'] }}</td>
                                        <td>
                                            <a href="{{ asset('storage/backups/' . $backup['filename']) }}"
                                               class="btn btn-sm btn-primary" download>
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <form action="{{ route('backup.delete') }}" method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this backup?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4">No backups found. Create your first backup above.</p>
                @endif
            </div>
        </div>
    </div>
</div>


@endsection

