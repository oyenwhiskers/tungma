@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Backup & Restore Management</h2>
            <p class="text-muted">Export and import bills data and media files</p>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Warning!</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Export Section --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📦 Export / Backup</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Create backups of your bills data and media files</p>

                    {{-- Export Data Button --}}
                    <form action="{{ route('backup.export.data') }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-file-earmark-text"></i> Backup Bills Data (JSON)
                        </button>
                        <small class="text-muted d-block mt-1">
                            Exports all bills with relationships to a JSON file
                        </small>
                    </form>

                    {{-- Export Media Button --}}
                    <form action="{{ route('backup.export.media') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-file-earmark-zip"></i> Backup Bills Media (ZIP)
                        </button>
                        <small class="text-muted d-block mt-1">
                            Exports all media files from storage/app/public/bills/
                        </small>
                    </form>
                </div>
            </div>
        </div>

        {{-- Import Section --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">📥 Import / Restore</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Restore bills data and media from backup files</p>

                    {{-- Import Data Form --}}
                    <form action="{{ route('backup.import.data') }}" method="POST" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label for="data_file" class="form-label">Restore Bills Data</label>
                            <input type="file" class="form-control @error('data_file') is-invalid @enderror"
                                   id="data_file" name="data_file" accept=".json,.txt" required>
                            @error('data_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-upload"></i> Restore Data from JSON
                        </button>
                        <small class="text-muted d-block mt-1">
                            Imports bills data from JSON backup file
                        </small>
                    </form>

                    {{-- Import Media Form --}}
                    <form action="{{ route('backup.import.media') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label for="media_file" class="form-label">Restore Bills Media</label>
                            <input type="file" class="form-control @error('media_file') is-invalid @enderror"
                                   id="media_file" name="media_file" accept=".zip" required>
                            @error('media_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="backup_existing" id="backup_existing">
                            <label class="form-check-label" for="backup_existing">
                                Backup existing media before restore
                            </label>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="bi bi-upload"></i> Restore Media from ZIP
                        </button>
                        <small class="text-muted d-block mt-1">
                            Extracts media files to storage/app/public/bills/
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Existing Backups List --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">📂 Existing Backups</h5>
                </div>
                <div class="card-body">
                    @if(count($backups) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                            <td>
                                                <small class="font-monospace">{{ $backup['filename'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $backup['type'] === 'Data' ? 'success' : 'info' }}">
                                                    {{ $backup['type'] }}
                                                </span>
                                            </td>
                                            <td>{{ $backup['size_human'] }}</td>
                                            <td>{{ $backup['date'] }}</td>
                                            <td>
                                                <a href="{{ asset('storage/backups/' . $backup['filename']) }}"
                                                   class="btn btn-sm btn-primary" download>
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                                <form action="{{ route('backup.delete') }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this backup?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Delete
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

    {{-- Important Notes --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6>📝 Important Notes:</h6>
                <ul class="mb-0">
                    <li><strong>Data Backup:</strong> Exports all bills including soft-deleted records with their relationships</li>
                    <li><strong>Media Backup:</strong> Creates a ZIP archive of all files in storage/app/public/bills/</li>
                    <li><strong>Data Restore:</strong> Updates existing records by ID or creates new ones. Skips invalid records.</li>
                    <li><strong>Media Restore:</strong> Extracts files to storage/app/public/bills/. Check "Backup existing" to preserve current files.</li>
                    <li><strong>Large Files:</strong> For very large media folders, the ZIP operation may take time. Be patient.</li>
                    <li><strong>Backup Storage:</strong> All backups are stored in storage/app/backups/</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        border-radius: 10px;
    }
    .card-header {
        border-radius: 10px 10px 0 0 !important;
        font-weight: 600;
    }
    .btn {
        border-radius: 6px;
        font-weight: 500;
    }
    .table {
        margin-bottom: 0;
    }
    .font-monospace {
        font-family: 'Courier New', monospace;
    }
</style>

@endsection

