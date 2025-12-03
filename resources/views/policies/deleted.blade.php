@extends('layouts.app')

@section('content')
<div class="tm-header">
  <div>
    <h2 class="mb-1">Deleted Policies</h2>
    <div class="text-muted">Restore previously removed courier policies</div>
  </div>
  <a href="{{ route('policies.index') }}" class="btn btn-outline-secondary">Back to Policies</a>
</div>

<div class="tm-card tm-table">
  <div class="tm-card-body">
    <table class="table">
      <thead><tr><th>Name</th><th>Deleted At</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @foreach($policies as $policy)
        <tr>
          <td>{{ $policy->name }}</td>
          <td>{{ $policy->deleted_at->format('Y-m-d H:i') }}</td>
          <td class="text-end">
            <form method="post" action="{{ route('policies.restore', $policy->id) }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-primary">Restore</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <div class="mt-2">{{ $policies->links() }}</div>
  </div>
</div>
@endsection
