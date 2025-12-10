@extends('layouts.app')

@section('content')
<div class="tm-header mb-3">
    <div>
        <h2 class="mb-1">{{ $policy->name }}</h2>
        <div class="text-muted">Courier policy details</div>
    </div>
    <div class="d-inline-flex gap-2">
        <a href="{{ route('policies.index') }}" class="btn btn-outline-secondary">Back</a>
        <a href="{{ route('policies.edit', $policy) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="tm-card">
    <div class="tm-card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Company</dt>
            <dd class="col-sm-9">{{ $policy->company?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9">{{ $policy->description ?: 'No description provided.' }}</dd>
        </dl>
    </div>
</div>
@endsection
