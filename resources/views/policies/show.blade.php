@extends('layouts.app')

@section('content')
<h2>Policy {{ $policy->name }}</h2>
<dl class="row">
  <dt class="col-sm-3">Company</dt><dd class="col-sm-9">{{ $policy->company?->name }}</dd>
  <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $policy->description }}</dd>
</dl>
<a href="{{ route('policies.edit', $policy) }}" class="btn btn-secondary">Edit</a>
@endsection
