@extends('layouts.app')

@section('content')
<div class="tm-header">
  <div>
    <h2 class="mb-1">Deleted Companies</h2>
    <div class="text-muted">Restore previously removed companies</div>
  </div>
</div>

<div class="tm-card tm-table">
  <div class="tm-card-body">
    <table class="table">
      <thead><tr><th>Name</th><th>Deleted At</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      @foreach($companies as $company)
        <tr>
          <td>{{ $company->name }}</td>
          <td>{{ $company->deleted_at }}</td>
          <td class="text-end">
            <form method="post" action="{{ route('companies.restore', $company->id) }}" class="d-inline">
              @csrf
              <button class="btn btn-sm btn-primary">Restore</button>
            </form>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <div class="mt-2">{{ $companies->links() }}</div>
  </div>
</div>
@endsection