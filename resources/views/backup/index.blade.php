@extends('layouts.app')

@section('content')
<div class="tm-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1">Backup &amp; Restore</h2>
        <div class="text-muted">Full system backups (database dump + receipt attachments) with live progress tracking</div>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cpanelHelpModal">
            <i class="bi bi-clock-history me-1"></i> cPanel Cron Setup
        </button>
    </div>
</div>

{{-- Alert Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ========================================================================= --}}
{{-- WINDOWS EXPLORER STYLE LIVE PROGRESS DIALOG                               --}}
{{-- ========================================================================= --}}
<div id="liveProgressContainer" class="card shadow-sm border mb-4" style="display: {{ ($activeJob && in_array($activeJob['status'], ['queued', 'in_progress'])) ? 'block' : 'none' }};">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <div id="progressSpinner" class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <i id="progressCompletedIcon" class="bi bi-check-circle-fill text-success fs-5" style="display: none;"></i>
            <i id="progressFailedIcon" class="bi bi-x-circle-fill text-danger fs-5" style="display: none;"></i>
            <span id="progressTitle" class="fw-semibold text-dark">
                {{ $activeJob['title'] ?? 'Processing Operation' }}
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="progressJobBadge" class="badge bg-secondary-subtle text-secondary border">
                {{ $activeJob['job_id'] ?? '' }}
            </span>
            <button type="button" id="btnDismissProgress" class="btn-close btn-sm" aria-label="Close" style="display: none;"></button>
        </div>
    </div>

    <div class="card-body p-4">
        {{-- Headline action text --}}
        <div class="d-flex justify-content-between align-items-baseline mb-1">
            <h5 id="progressStage" class="card-title fw-bold text-dark mb-0">
                {{ $activeJob['stage'] ?? 'Preparing job...' }}
            </h5>
            <span id="progressPercentage" class="fs-5 fw-bold text-primary">
                {{ $activeJob['percentage'] ?? 0 }}%
            </span>
        </div>

        {{-- Sub-details / counter --}}
        <div class="text-muted small mb-3 d-flex justify-content-between flex-wrap gap-2">
            <span id="progressCounter">
                {{ $activeJob['counter_text'] ?? '0 / 0 items' }}
            </span>
            <span id="progressCurrentFile" class="text-truncate text-secondary" style="max-width: 380px;">
                {{ $activeJob['current_file'] ?? '' }}
            </span>
        </div>

        {{-- Progress Bar (Windows Explorer Style) --}}
        <div class="progress" style="height: 18px; border-radius: 6px; background-color: #e9ecef;">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                 role="progressbar"
                 style="width: {{ $activeJob['percentage'] ?? 0 }}%; transition: width 0.3s ease;">
            </div>
        </div>

        {{-- Success State Download Banner --}}
        <div id="progressSuccessAlert" class="alert alert-success mt-3 mb-0 d-none justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span id="progressSuccessMessage">Backup completed successfully!</span>
            </div>
            <div id="progressDownloadContainer">
                <a href="#" id="progressDownloadBtn" class="btn btn-sm btn-success fw-semibold">
                    <i class="bi bi-download me-1"></i> Download Archive (.zip)
                </a>
            </div>
        </div>

        {{-- Error State Banner --}}
        <div id="progressErrorAlert" class="alert alert-danger mt-3 mb-0 d-none">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                <div>
                    <div class="fw-bold">Operation failed</div>
                    <div id="progressErrorMessage" class="small">An error occurred during execution.</div>
                </div>
            </div>
        </div>

        {{-- Queued Status Notice Banner --}}
        <div id="progressQueuedNotice" class="alert alert-warning mt-3 mb-0 py-2 small d-none justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <i class="bi bi-info-circle-fill me-1"></i>
                <span>Job is queued. Waiting for queue worker process...</span>
            </div>
            <button type="button" class="btn btn-sm btn-dark py-0 px-2" id="btnRunWorkerManual">
                <i class="bi bi-play-fill"></i> Process Queue Now
            </button>
        </div>

        {{-- Expandable "View details" (Windows Explorer Style) --}}
        <div class="mt-3">
            <button class="btn btn-link btn-sm text-decoration-none p-0 text-muted d-flex align-items-center gap-1"
                    type="button" data-bs-toggle="collapse" data-bs-target="#progressDetailsCollapse"
                    aria-expanded="false" id="toggleDetailsBtn">
                <i class="bi bi-chevron-right transition-icon" id="toggleDetailsIcon"></i>
                <span id="toggleDetailsText">View details</span>
            </button>

            <div class="collapse mt-2" id="progressDetailsCollapse">
                <div class="p-3 bg-dark text-light rounded font-monospace small"
                     id="progressConsole"
                     style="max-height: 220px; overflow-y: auto; font-size: 0.82rem; line-height: 1.5; border: 1px solid #333;">
                    @if(!empty($activeJob['logs']))
                        @foreach($activeJob['logs'] as $log)
                            <div><span class="text-secondary">[{{ $log['time'] ?? '' }}]</span> {{ $log['message'] ?? '' }}</div>
                        @endforeach
                    @else
                        <div class="text-secondary">[Initializing...] Ready.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- ACTION CARDS: BACKUP NOW & RESTORE SYSTEM                                --}}
{{-- ========================================================================= --}}
<div class="row g-3">
    {{-- BACKUP CARD --}}
    <div class="col-lg-6">
        <div class="tm-card h-100">
            <div class="tm-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cloud-arrow-down me-2"></i> Create Full Backup</span>
                <span class="badge bg-primary-subtle text-primary">Non-blocking Queue</span>
            </div>
            <div class="tm-card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted mb-3">
                        Generates a single ZIP archive containing:
                    </p>
                    <ul class="list-unstyled small mb-4">
                        <li class="mb-2">
                            <i class="bi bi-database-check text-success me-2"></i>
                            <strong>database.sql.gz</strong>: Complete MySQL database dump (pure-PHP streaming fallback if <code>mysqldump</code> is disabled on cPanel).
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-images text-primary me-2"></i>
                            <strong>images/</strong>: All receipt attachments from <code>storage/app/public</code>, preserving original relative directory paths.
                        </li>
                        <li class="text-muted">
                            <i class="bi bi-cpu text-secondary me-2"></i>
                            Dispatched as a background job so the web request never times out or runs out of memory.
                        </li>
                    </ul>
                </div>

                <div>
                    <button type="button" class="btn btn-primary w-100 btn-lg shadow-sm" id="btnTriggerBackup">
                        <i class="bi bi-play-circle me-2"></i> Backup Now
                    </button>
                    <small class="text-muted d-block text-center mt-2">
                        Saved to <code>storage/app/backups/</code> with auto-retention cleanup.
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- RESTORE CARD --}}
    <div class="col-lg-6">
        <div class="tm-card h-100 border-warning">
            <div class="tm-card-header bg-warning-subtle text-warning-emphasis d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-counterclockwise me-2"></i> Restore System</span>
                <span class="badge bg-danger">Destructive</span>
            </div>
            <div class="tm-card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Full Replacement:</strong> Completely replaces database tables and overwrites receipt files in <code>storage/app/public</code>.
                    </div>

                    <p class="text-muted small mb-3">
                        You can restore from a previously created server backup or upload an external backup ZIP.
                    </p>

                    <div class="row g-2 small text-muted mb-3">
                        <div class="col-6">Upload limit: <strong>{{ $serverLimits['upload_max_filesize'] }}</strong></div>
                        <div class="col-6">Post limit: <strong>{{ $serverLimits['post_max_size'] }}</strong></div>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn btn-outline-danger w-100 btn-lg" data-bs-toggle="modal" data-bs-target="#restoreModal">
                        <i class="bi bi-box-arrow-in-down me-2"></i> Open Restore Wizard...
                    </button>
                    <small class="text-muted d-block text-center mt-2">
                        Requires typing <code>{{ $confirmPhrase }}</code> to proceed.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- STORAGE METRICS & MAINTENANCE                                             --}}
{{-- ========================================================================= --}}
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="tm-card">
            <div class="tm-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hdd me-2"></i> Storage Metrics</span>
                <a href="{{ route('backup.delete.bills') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-trash me-1"></i> Manage Bill Deletion
                </a>
            </div>
            <div class="tm-card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Storage Area</th>
                                <th>Size (MB)</th>
                                <th>Server Path</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Backups</strong></td>
                                <td>{{ number_format($metrics['backups'] / 1048576, 2) }} MB</td>
                                <td><small class="text-muted">storage/app/backups</small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearStorageModal" data-target="backups" data-label="Backups" data-warning="Clear all backups? This cannot be undone!">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Media Files</strong></td>
                                <td>{{ number_format($metrics['media'] / 1048576, 2) }} MB</td>
                                <td><small class="text-muted">storage/app/public</small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearStorageModal" data-target="media" data-label="Media Files" data-warning="Clear all media files? This will delete receipt attachments and other uploads!">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Logs</strong></td>
                                <td>{{ number_format($metrics['logs'] / 1048576, 2) }} MB</td>
                                <td><small class="text-muted">storage/logs</small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearStorageModal" data-target="logs" data-label="Logs" data-warning="Clear all log files?">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- EXISTING BACKUPS TABLE                                                    --}}
{{-- ========================================================================= --}}
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="tm-card">
            <div class="tm-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-folder2-open me-2"></i> Existing Backups on Server</span>
                <span class="badge bg-secondary">{{ count($backups) }} Available</span>
            </div>
            <div class="tm-card-body">
                @if(count($backups) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Date Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-zip text-primary me-2 fs-5"></i>
                                            <strong>{{ $backup['filename'] }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary border">{{ $backup['type'] }}</span>
                                        </td>
                                        <td>{{ $backup['size_human'] }}</td>
                                        <td><small class="text-muted">{{ $backup['date'] }}</small></td>
                                        <td class="text-end">
                                            <a href="{{ route('backup.download', ['filename' => $backup['filename']]) }}"
                                               class="btn btn-sm btn-primary" title="Download ZIP">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning btn-select-restore"
                                                    data-filename="{{ $backup['filename'] }}"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal"
                                                    title="Restore this backup">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                            <form action="{{ route('backup.delete') }}" method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Permanently delete {{ $backup['filename'] }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
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
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-archive display-6 d-block mb-2 text-secondary"></i>
                        No backup archives found in <code>storage/app/backups</code>. Click "Backup Now" above to create one.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: RESTORE CONFIRMATION WIZARD                                        --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="restoreModalLabel">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i> Confirm System Restore
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="restoreForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger mb-4">
                        <h6 class="alert-heading fw-bold mb-1">
                            <i class="bi bi-radioactive me-1"></i> CRITICAL WARNING: DESTRUCTIVE ACTION
                        </h6>
                        <p class="mb-0 small">
                            Restoring will <strong>completely DROP and REPLACE all current database tables</strong> and overwrite images in <code>storage/app/public</code>. Any data created after this backup will be permanently lost!
                        </p>
                    </div>

                    {{-- Step 1: Choose Source --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">1. Select Backup Source</label>
                        <ul class="nav nav-pills nav-fill mb-3" id="restoreSourceTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-existing-btn" data-bs-toggle="pill" data-bs-target="#tab-existing" type="button">
                                    <i class="bi bi-folder-check me-1"></i> Choose from Server
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-upload-btn" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button">
                                    <i class="bi bi-upload me-1"></i> Upload ZIP File
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="restoreSourceTabContent">
                            {{-- Server Backups Tab --}}
                            <div class="tab-pane fade show active" id="tab-existing" role="tabpanel">
                                <select class="form-select" id="existing_backup_select" name="existing_backup">
                                    <option value="">-- Choose a backup file on server --</option>
                                    @foreach($backups as $b)
                                        <option value="{{ $b['filename'] }}">{{ $b['filename'] }} ({{ $b['size_human'] }} - {{ $b['date'] }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Upload Tab with Chunked 10MB Streaming --}}
                            <div class="tab-pane fade" id="tab-upload" role="tabpanel">
                                <div class="p-3 border rounded bg-light mb-2">
                                    <label for="restore_file_input" class="form-label fw-semibold">
                                        <i class="bi bi-file-earmark-zip me-1 text-primary"></i> Select Backup ZIP Archive
                                    </label>
                                    <input type="file" class="form-control" id="restore_file_input" accept=".zip">

                                    <div id="fileSelectionInfo" class="mt-2 text-primary small fw-semibold" style="display: none;">
                                        <i class="bi bi-check2-circle me-1"></i> Ready to upload: <span id="selectedFileName"></span> (<span id="selectedFileSize"></span>)
                                    </div>

                                    <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
                                        <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>
                                        <strong>Chunked 10MB Streaming Active:</strong> Slices archives of any size (even 10GB–20GB+) into small 10MB chunks directly in your browser. No cPanel file hunting, no PHP upload limits, and no timeouts!
                                    </div>
                                </div>

                                {{-- Live Upload Progress Bar during chunk transmission --}}
                                <div id="chunkedUploadProgressContainer" class="p-3 border rounded bg-white shadow-sm mt-2" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold small text-dark" id="chunkedUploadStage">Uploading chunks to server...</span>
                                        <span class="badge bg-primary" id="chunkedUploadPct">0%</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 12px;">
                                        <div id="chunkedProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span id="chunkedUploadCounter">0 / 0 chunks</span>
                                        <span id="chunkedUploadBytes">0 MB / 0 MB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Confirmation Phrase --}}
                    <div class="mb-3 pt-3 border-top">
                        <label for="confirm_phrase_input" class="form-label fw-bold">
                            2. Type <span class="badge bg-danger">{{ $confirmPhrase }}</span> to unlock restore:
                        </label>
                        <input type="text" class="form-control form-control-lg font-monospace text-uppercase"
                               id="confirm_phrase_input" name="confirm_phrase"
                               placeholder="Type {{ $confirmPhrase }} here" autocomplete="off" required>
                        <div id="phraseMatchFeedback" class="form-text text-danger" style="display: none;">
                            Confirmation phrase does not match.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="btnSubmitRestore" disabled>
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Start Destructive Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: CPANEL CRON JOB SETUP GUIDE                                        --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="cpanelHelpModal" tabindex="-1" aria-labelledby="cpanelHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cpanelHelpModalLabel">
                    <i class="bi bi-clock-history text-primary me-2"></i> cPanel Shared Hosting Setup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <h6>Why is this needed?</h6>
                <p class="small text-muted">
                    On cPanel shared hosting, background supervisor daemons are not available. To process large backups and restores smoothly without web timeouts, configure a cPanel Cron Job to run Laravel's queue worker every minute. The worker processes the queued job and automatically shuts down once finished (<code>--stop-when-empty</code>), using zero server resources when idle.
                </p>

                <h6 class="mt-4">cPanel Cron Job Command:</h6>
                <div class="bg-dark text-light p-3 rounded font-monospace small mb-2 position-relative">
                    <code>* * * * * cd {{ base_path() }} &amp;&amp; /usr/local/bin/php artisan queue:work --stop-when-empty &gt;&gt; /dev/null 2&gt;&amp;1</code>
                </div>
                <small class="text-muted d-block mb-3">
                    <em>Tip: In cPanel &gt; Cron Jobs, set interval to <strong>Once Per Minute (* * * * *)</strong>. If <code>/usr/local/bin/php</code> does not match your host, check your cPanel PHP version or use <code>ea-php82</code> or <code>which php</code>.</em>
                </small>

                <h6 class="mt-4">PHP Upload Limit Tips (for Restore):</h6>
                <p class="small text-muted mb-0">
                    If your backup zip is large (e.g., 200MB+), upload limits might prevent HTTP uploads. You have two easy options:
                    <br>1. <strong>Direct File Manager Upload:</strong> Upload the <code>.zip</code> directly into <code>storage/app/backups/</code> via cPanel File Manager, then select it in the Restore Wizard.
                    <br>2. <strong>Adjust PHP Limits:</strong> In cPanel &gt; MultiPHP INI Editor, increase <code>upload_max_filesize</code> and <code>post_max_size</code> to <code>512M</code>.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Password Confirmation for Storage Clearing --}}
<div class="modal fade" id="clearStorageModal" tabindex="-1" aria-labelledby="clearStorageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearStorageModalLabel">Confirm Storage Clearing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clearStorageForm" method="POST" action="{{ route('backup.clear.storage') }}">
                @csrf
                <input type="hidden" name="target" id="modalTarget">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="modalWarning"></span>
                    </div>
                    <p class="mb-3">Please enter your Super Admin password to confirm:</p>
                    <div class="mb-3">
                        <label for="modalPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="modalPassword" name="password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Clear</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- JAVASCRIPT: LIVE PROGRESS POLLING & EVENT HANDLING                       --}}
{{-- ========================================================================= --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const requiredPhrase = "{{ $confirmPhrase }}";
    let pollInterval = null;
    let currentJobId = @json($activeJob['job_id'] ?? null);
    let activeJobStatus = @json($activeJob['status'] ?? null);

    // UI elements
    const liveContainer = document.getElementById('liveProgressContainer');
    const titleEl = document.getElementById('progressTitle');
    const stageEl = document.getElementById('progressStage');
    const pctEl = document.getElementById('progressPercentage');
    const counterEl = document.getElementById('progressCounter');
    const currentFileEl = document.getElementById('progressCurrentFile');
    const progressBarEl = document.getElementById('progressBar');
    const jobBadgeEl = document.getElementById('progressJobBadge');
    const consoleEl = document.getElementById('progressConsole');
    const spinnerEl = document.getElementById('progressSpinner');
    const completedIconEl = document.getElementById('progressCompletedIcon');
    const failedIconEl = document.getElementById('progressFailedIcon');
    const successAlertEl = document.getElementById('progressSuccessAlert');
    const successMsgEl = document.getElementById('progressSuccessMessage');
    const downloadContainerEl = document.getElementById('progressDownloadContainer');
    const downloadBtnEl = document.getElementById('progressDownloadBtn');
    const errorAlertEl = document.getElementById('progressErrorAlert');
    const errorMsgEl = document.getElementById('progressErrorMessage');
    const queuedNoticeEl = document.getElementById('progressQueuedNotice');
    const btnRunWorkerManual = document.getElementById('btnRunWorkerManual');
    const dismissBtnEl = document.getElementById('btnDismissProgress');
    const btnTriggerBackup = document.getElementById('btnTriggerBackup');

    // Details Toggle icon rotation
    const detailsBtn = document.getElementById('toggleDetailsBtn');
    const detailsIcon = document.getElementById('toggleDetailsIcon');
    const detailsText = document.getElementById('toggleDetailsText');
    const detailsCollapse = document.getElementById('progressDetailsCollapse');

    if (detailsCollapse) {
        detailsCollapse.addEventListener('show.bs.collapse', function() {
            detailsIcon.classList.add('rotate-down');
            detailsText.textContent = 'Fewer details';
        });
        detailsCollapse.addEventListener('hide.bs.collapse', function() {
            detailsIcon.classList.remove('rotate-down');
            detailsText.textContent = 'View details';
        });
    }

    // Dismiss progress card
    dismissBtnEl.addEventListener('click', function() {
        liveContainer.style.display = 'none';
        if (pollInterval) clearInterval(pollInterval);
    });

    // Manual queue trigger button
    if (btnRunWorkerManual) {
        btnRunWorkerManual.addEventListener('click', function() {
            btnRunWorkerManual.disabled = true;
            btnRunWorkerManual.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

            fetch("{{ route('backup.process.queue') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(d => {
                if (currentJobId) {
                    fetch("{{ url('/backup/status') }}/" + currentJobId)
                        .then(r => r.json())
                        .then(data => updateProgressUI(data));
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                btnRunWorkerManual.disabled = false;
                btnRunWorkerManual.innerHTML = '<i class="bi bi-play-fill"></i> Process Queue Now';
            });
        });
    }

    // If active job is running on page load, start polling immediately
    if (currentJobId && (activeJobStatus === 'queued' || activeJobStatus === 'in_progress')) {
        startPolling(currentJobId);
    }

    // -------------------------------------------------------------
    // Trigger Backup
    // -------------------------------------------------------------
    btnTriggerBackup.addEventListener('click', function() {
        if (!confirm('Start full system backup now? This will run in the background.')) {
            return;
        }

        btnTriggerBackup.disabled = true;
        btnTriggerBackup.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Queuing Backup...';

        fetch("{{ route('backup.run') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.job_id) {
                currentJobId = data.job_id;
                showProgressDialog('Creating Full System Backup', data.job_id);
                startPolling(data.job_id);
            } else {
                alert(data.message || 'Failed to dispatch backup job.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Server error dispatching backup job.');
        })
        .finally(() => {
            btnTriggerBackup.disabled = false;
            btnTriggerBackup.innerHTML = '<i class="bi bi-play-circle me-2"></i> Backup Now';
        });
    });

    // -------------------------------------------------------------
    // Restore Form & Confirmation Phrase Validation
    // -------------------------------------------------------------
    const confirmInput = document.getElementById('confirm_phrase_input');
    const submitRestoreBtn = document.getElementById('btnSubmitRestore');
    const restoreForm = document.getElementById('restoreForm');
    const restoreModalEl = document.getElementById('restoreModal');
    const restoreFileInput = document.getElementById('restore_file_input');
    const fileSelectionInfo = document.getElementById('fileSelectionInfo');
    const selectedFileName = document.getElementById('selectedFileName');
    const selectedFileSize = document.getElementById('selectedFileSize');

    let activeRestoreTab = 'existing';
    const tabExistingBtn = document.getElementById('tab-existing-btn');
    const tabUploadBtn = document.getElementById('tab-upload-btn');

    if (tabExistingBtn) tabExistingBtn.addEventListener('click', () => activeRestoreTab = 'existing');
    if (tabUploadBtn) tabUploadBtn.addEventListener('click', () => activeRestoreTab = 'upload');

    // Display file size information when file selected
    if (restoreFileInput) {
        restoreFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const f = this.files[0];
                selectedFileName.textContent = f.name;
                const mb = f.size / (1024 * 1024);
                selectedFileSize.textContent = mb >= 1024 ? (mb / 1024).toFixed(2) + ' GB' : mb.toFixed(1) + ' MB';
                fileSelectionInfo.style.display = 'block';
            } else {
                fileSelectionInfo.style.display = 'none';
            }
        });
    }

    confirmInput.addEventListener('input', function() {
        const matches = this.value.trim().toUpperCase() === requiredPhrase;
        submitRestoreBtn.disabled = !matches;
    });

    // Handle "Restore this backup" buttons in existing backups table
    document.querySelectorAll('.btn-select-restore').forEach(button => {
        button.addEventListener('click', function() {
            const filename = this.getAttribute('data-filename');
            const selectEl = document.getElementById('existing_backup_select');
            if (selectEl) {
                selectEl.value = filename;
                activeRestoreTab = 'existing';
                if (tabExistingBtn) {
                    bootstrap.Tab.getOrCreateInstance(tabExistingBtn).show();
                }
            }
        });
    });

    restoreForm.addEventListener('submit', function(e) {
        e.preventDefault();

        submitRestoreBtn.disabled = true;

        if (activeRestoreTab === 'upload') {
            const file = restoreFileInput.files[0];
            if (!file) {
                alert('Please select a backup ZIP archive to upload.');
                submitRestoreBtn.disabled = false;
                return;
            }

            // Start 10MB chunked upload
            const CHUNK_SIZE = 10 * 1024 * 1024; // 10MB
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
            const fileId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);

            const chunkedContainer = document.getElementById('chunkedUploadProgressContainer');
            const chunkedStage = document.getElementById('chunkedUploadStage');
            const chunkedPct = document.getElementById('chunkedUploadPct');
            const chunkedBar = document.getElementById('chunkedProgressBar');
            const chunkedCounter = document.getElementById('chunkedUploadCounter');
            const chunkedBytes = document.getElementById('chunkedUploadBytes');

            chunkedContainer.style.display = 'block';

            let currentChunk = 0;

            const uploadNextChunk = () => {
                const start = currentChunk * CHUNK_SIZE;
                const end = Math.min(file.size, start + CHUNK_SIZE);
                const chunkBlob = file.slice(start, end);

                const formData = new FormData();
                formData.append('file_id', fileId);
                formData.append('chunk_index', currentChunk);
                formData.append('total_chunks', totalChunks);
                formData.append('filename', file.name);
                formData.append('chunk', chunkBlob, 'chunk.bin');

                chunkedStage.textContent = `Streaming chunk ${currentChunk + 1} of ${totalChunks}...`;
                const pct = Math.round(((currentChunk + 1) / totalChunks) * 100);
                submitRestoreBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Uploading ${pct}%...`;

                fetch("{{ route('backup.upload.chunk') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Chunk upload failed');
                    }

                    chunkedBar.style.width = pct + '%';
                    chunkedPct.textContent = pct + '%';
                    chunkedCounter.textContent = `Chunk ${currentChunk + 1} of ${totalChunks}`;
                    chunkedBytes.textContent = `${(end / (1024 * 1024)).toFixed(1)} MB / ${(file.size / (1024 * 1024)).toFixed(1)} MB`;

                    if (data.completed) {
                        chunkedStage.textContent = 'Archive assembled! Queuing restore...';
                        triggerRestoreJob({
                            confirm_phrase: confirmInput.value.trim(),
                            uploaded_temp_filename: data.assembled_filename
                        });
                    } else {
                        currentChunk++;
                        uploadNextChunk();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Chunked upload failed: ' + err.message);
                    submitRestoreBtn.disabled = false;
                    submitRestoreBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Start Destructive Restore';
                });
            };

            uploadNextChunk();

        } else {
            // Existing file on server
            const selectEl = document.getElementById('existing_backup_select');
            if (!selectEl.value) {
                alert('Please select a backup file from the server.');
                submitRestoreBtn.disabled = false;
                return;
            }

            submitRestoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Dispatching Restore...';

            triggerRestoreJob({
                confirm_phrase: confirmInput.value.trim(),
                existing_backup: selectEl.value
            });
        }
    });

    function triggerRestoreJob(payload) {
        fetch("{{ route('backup.restore.run') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.job_id) {
                const modal = bootstrap.Modal.getInstance(restoreModalEl);
                if (modal) modal.hide();
                restoreForm.reset();
                submitRestoreBtn.disabled = true;

                currentJobId = data.job_id;
                showProgressDialog('Restoring Full System Backup', data.job_id);
                startPolling(data.job_id);
            } else {
                alert(data.message || 'Failed to dispatch restore job.');
                submitRestoreBtn.disabled = false;
                submitRestoreBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Start Destructive Restore';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Server error dispatching restore job.');
            submitRestoreBtn.disabled = false;
            submitRestoreBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Start Destructive Restore';
        });
    }

    // -------------------------------------------------------------
    // Progress Dialog Display and Polling Engine
    // -------------------------------------------------------------
    function showProgressDialog(title, jobId) {
        liveContainer.style.display = 'block';
        titleEl.textContent = title;
        jobBadgeEl.textContent = jobId;
        stageEl.textContent = 'Queued in background...';
        pctEl.textContent = '0%';
        counterEl.textContent = 'Waiting for queue worker...';
        currentFileEl.textContent = '';
        progressBarEl.style.width = '0%';
        progressBarEl.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';

        spinnerEl.style.display = 'inline-block';
        completedIconEl.style.display = 'none';
        failedIconEl.style.display = 'none';
        dismissBtnEl.style.display = 'none';

        successAlertEl.classList.add('d-none');
        successAlertEl.classList.remove('d-flex');
        errorAlertEl.classList.add('d-none');
        queuedNoticeEl.classList.remove('d-none');
        queuedNoticeEl.classList.add('d-flex');

        consoleEl.innerHTML = '<div class="text-secondary">[' + (new Date()).toLocaleTimeString() + '] Job queued. Waiting for queue worker...</div>';

        // Auto-scroll to dialog
        liveContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function startPolling(jobId) {
        if (pollInterval) clearInterval(pollInterval);

        const statusUrl = "{{ url('/backup/status') }}/" + jobId;

        const checkStatus = () => {
            fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Status poll failed');
                return res.json();
            })
            .then(data => {
                updateProgressUI(data);

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollInterval);
                }
            })
            .catch(err => {
                console.warn('Polling notice: ', err);
            });
        };

        // Immediate check then 1.5s interval
        checkStatus();
        pollInterval = setInterval(checkStatus, 1500);
    }

    function updateProgressUI(data) {
        if (!data) return;

        if (data.title) titleEl.textContent = data.title;
        if (data.stage) stageEl.textContent = data.stage;
        if (data.counter_text) counterEl.textContent = data.counter_text;
        if (data.current_file !== undefined) currentFileEl.textContent = data.current_file;

        const pct = data.percentage || 0;
        pctEl.textContent = pct + '%';
        progressBarEl.style.width = pct + '%';

        // Update console logs
        if (Array.isArray(data.logs) && data.logs.length > 0) {
            const html = data.logs.map(log => `<div><span class="text-secondary">[${log.time || ''}]</span> ${escapeHtml(log.message || '')}</div>`).join('');
            consoleEl.innerHTML = html;
            consoleEl.scrollTop = consoleEl.scrollHeight;
        }

        // Status states
        if (data.status === 'completed') {
            spinnerEl.style.display = 'none';
            completedIconEl.style.display = 'inline-block';
            failedIconEl.style.display = 'none';
            progressBarEl.className = 'progress-bar bg-success';
            progressBarEl.style.width = '100%';
            dismissBtnEl.style.display = 'inline-block';

            queuedNoticeEl.classList.add('d-none');
            queuedNoticeEl.classList.remove('d-flex');
            errorAlertEl.classList.add('d-none');
            successAlertEl.classList.remove('d-none');
            successAlertEl.classList.add('d-flex');

            if (data.type === 'backup' && data.result && data.result.download_url) {
                successMsgEl.textContent = `Backup archive created: ${data.result.filename} (${data.result.size_human})`;
                downloadContainerEl.style.display = 'block';
                downloadBtnEl.href = data.result.download_url;
            } else if (data.type === 'restore') {
                successMsgEl.textContent = 'System restore finished successfully! Database overwritten and images extracted.';
                downloadContainerEl.style.display = 'none';
            }
        } else if (data.status === 'failed') {
            spinnerEl.style.display = 'none';
            completedIconEl.style.display = 'none';
            failedIconEl.style.display = 'inline-block';
            progressBarEl.className = 'progress-bar bg-danger';
            dismissBtnEl.style.display = 'inline-block';

            queuedNoticeEl.classList.add('d-none');
            queuedNoticeEl.classList.remove('d-flex');
            successAlertEl.classList.add('d-none');
            successAlertEl.classList.remove('d-flex');
            errorAlertEl.classList.remove('d-none');
            errorMsgEl.textContent = data.error || 'The job encountered an unexpected error.';
        } else if (data.status === 'queued') {
            spinnerEl.style.display = 'inline-block';
            completedIconEl.style.display = 'none';
            failedIconEl.style.display = 'none';
            successAlertEl.classList.add('d-none');
            successAlertEl.classList.remove('d-flex');
            errorAlertEl.classList.add('d-none');
            queuedNoticeEl.classList.remove('d-none');
            queuedNoticeEl.classList.add('d-flex');
        } else if (data.status === 'in_progress') {
            spinnerEl.style.display = 'inline-block';
            completedIconEl.style.display = 'none';
            failedIconEl.style.display = 'none';
            successAlertEl.classList.add('d-none');
            successAlertEl.classList.remove('d-flex');
            errorAlertEl.classList.add('d-none');
            queuedNoticeEl.classList.add('d-none');
            queuedNoticeEl.classList.remove('d-flex');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // -------------------------------------------------------------
    // Clear Storage Modal
    // -------------------------------------------------------------
    const clearStorageModal = document.getElementById('clearStorageModal');
    if (clearStorageModal) {
        clearStorageModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                document.getElementById('modalTarget').value = button.getAttribute('data-target') || '';
                document.getElementById('modalWarning').textContent = button.getAttribute('data-warning') || '';
                document.getElementById('modalPassword').value = '';
            }
        });
    }
});
</script>

<style>
.transition-icon {
    display: inline-block;
    transition: transform 0.2s ease-in-out;
}
.rotate-down {
    transform: rotate(90deg);
}
</style>
@endsection
