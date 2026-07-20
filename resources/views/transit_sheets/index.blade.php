@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <span>Transit Sheets</span>
</div>

<div class="tm-header mb-3">
    <div>
        <h2 class="mb-1">Transit Sheets / Manifests</h2>
        <div class="text-muted">
            Generate and view waybill dispatch/transit summaries between branches
        </div>
    </div>
</div>

<div class="row">
    <!-- Filter Card -->
    <div class="col-12 mb-4">
        <div class="tm-card">
            <div class="tm-card-header">
                <i class="bi bi-funnel me-2" style="color:var(--tm-primary);"></i>
                Filter Manifest Parameters
            </div>
            <div class="tm-card-body">
                <form method="GET" action="{{ route('transit-sheets.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="from_company_id" class="form-label">Sender Branch (From)</label>
                            <select name="from_company_id" id="from_company_id" class="form-select" required>
                                <option value="">-- Select Sender --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ $fromCompanyId == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->bill_id_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="to_company_id" class="form-label">Receiver Branch (To)</label>
                            <select name="to_company_id" id="to_company_id" class="form-select">
                                <option value="" {{ empty($toCompanyIds) ? 'selected' : '' }}>-- All Receivers --</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ in_array($company->id, (array)$toCompanyIds) ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->bill_id_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
                        </div>

                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('transit-sheets.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Preview -->
    @if($showPdf)
        <div class="col-12">
            <div class="tm-card">
                <div class="tm-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-file-pdf me-2" style="color:var(--tm-danger);"></i>
                        PDF Viewer
                    </div>
                    <a href="{{ $pdfUrl }}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Open in New Tab
                    </a>
                </div>
                <div class="tm-card-body p-0">
                    <iframe src="{{ $pdfUrl }}" width="100%" height="800px" style="border: none;"></iframe>
                </div>
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="text-center text-muted py-5 tm-card">
                <div class="tm-card-body">
                    <i class="bi bi-arrow-up-circle d-block mb-3" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    Select Sender Branch and Date range above to load bills.
                </div>
            </div>
        </div>
    @endif
</div>
@endsection


