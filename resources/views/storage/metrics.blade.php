@extends('layouts.app')

@section('content')
<h2>Storage Metrics</h2>
<table class="table">
  <thead><tr><th>Area</th><th>Size (MB)</th><th></th></tr></thead>
  <tbody>
    @foreach($metrics as $area => $bytes)
      <tr>
        <td>{{ $area }}</td>
        <td>{{ number_format($bytes / 1048576, 2) }}</td>
        <td>
          <form method="post" action="{{ route('storage.clear') }}">
            @csrf
            <input type="hidden" name="target" value="{{ $area }}">
            <button class="btn btn-sm btn-danger">Clear</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
