@extends('layouts.app')

@section('content')
    <div class="tm-header">
        <div>
            <h2 class="mb-1">Checklist Details</h2>
            <div class="text-muted">
                Departure: {{ \Carbon\Carbon::parse($bus_datetime)->format('d M Y, h:i A') }}
            </div>
        </div>
        <div>
            <a href="{{ route('checklists.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ route('checklists.save') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-12">
                <div class="tm-card">
                    <div class="tm-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-receipt me-2" style="color:var(--tm-primary);"></i>
                            Items / Bills
                        </div>
                        <div>
                            <span class="badge bg-info text-dark">{{ $bills->count() }} items</span>
                        </div>
                    </div>
                    <div class="tm-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">
                                            <input type="checkbox" class="form-check-input" id="checkAll">
                                        </th>
                                        <th>Bill Code</th>
                                        <th>Sender</th>
                                        <th>Receiver</th>
                                        <th>Amount</th>
                                        <th>Verification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bills as $bill)
                                        <tr class="{{ $bill->checked_by ? 'table-success' : '' }}">
                                            <td>
                                                <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}"
                                                    class="form-check-input bill-checkbox" {{ $bill->checked_by ? 'checked' : '' }}>
                                            </td>
                                            <td>
                                                <strong>{{ $bill->bill_code }}</strong>
                                            </td>
                                            <td>
                                                {{ $bill->sender_name }}<br>
                                                <small class="text-muted">{{ $bill->sender_phone }}</small>
                                            </td>
                                            <td>
                                                {{ $bill->receiver_name }}<br>
                                                <small class="text-muted">{{ $bill->receiver_phone }}</small>
                                            </td>
                                            <td>RM {{ number_format($bill->amount, 2) }}</td>
                                            <td>
                                                @if($bill->checked_by)
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> {{ $bill->checked_by }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                No bills found for this departure.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tm-card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Checklist
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.getElementById('checkAll').addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('.bill-checkbox');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = this.checked;
                }, this);
            });
        </script>
    @endpush
@endsection