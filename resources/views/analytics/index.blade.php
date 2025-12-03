@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Analytics</h2>
  <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="mb-4">
  <h4>Total Revenue</h4>
  <div class="fs-3">RM {{ number_format($totalRevenue, 2) }}</div>
 </div>

<div class="mb-4">
  <h4>Staff Distribution per Company</h4>
  <table class="table table-sm">
    <thead><tr><th>Company</th><th>Staff</th></tr></thead>
    <tbody>
      @foreach($staffDistribution as $row)
        <tr><td>{{ $row['company'] }}</td><td>{{ $row['total'] }}</td></tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="mb-4">
  <h4>Bill Summaries</h4>
  <table class="table table-sm">
    <thead><tr><th>Company</th><th>Bills</th><th>Revenue (RM)</th></tr></thead>
    <tbody>
      @foreach($billSummaries as $row)
        <tr><td>{{ $row['company'] }}</td><td>{{ $row['bills'] }}</td><td>{{ number_format($row['revenue'], 2) }}</td></tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
