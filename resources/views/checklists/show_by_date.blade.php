@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('checklists.index') }}">Checklists</a>
    <i class="bi bi-chevron-right"></i>
    <span>Main Proof Checklist (Daily)</span>
</div>

<div class="tm-header">
    <div>
        <h2 class="mb-1">Main Proof Checklist (Daily)</h2>
        <div class="text-muted">
            Date: {{ \Carbon\Carbon::parse($date)->format('d M Y') }} (All Departures)
        </div>
    </div>
    <div>
        <a href="{{ route('checklists.printByDate', ['date' => $date, 'type' => $type]) }}" target="_blank" class="btn btn-primary me-2">
            <i class="bi bi-print"></i> Print Proof of Collection
        </a>
        <a href="{{ route('checklists.index', ['date' => $date, 'type' => $type]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

    @php
        $canEdit = true;
    @endphp

    <form action="{{ route('checklists.saveByDate') }}" method="POST">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="row text-end mb-3">
            <div class="col-12">
                @if(session('status'))
                    <div class="alert alert-success d-inline-block py-1 px-3 mb-0 small">
                        {{ session('status') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="tm-card">
                    <div class="tm-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-receipt me-2" style="color:var(--tm-primary);"></i>
                            Items / Bills (Sorted by Running Number)
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
                                            <input type="checkbox" class="form-check-input" id="checkAll" {{ !$canEdit ? 'disabled' : '' }}>
                                        </th>
                                        <th>Bill Code</th>
                                        <th>Departure</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Amount</th>
                                        <th>Verification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bills as $bill)
                                        <tr class="{{ $bill->checked_by ? 'table-success' : '' }} clickable-row" 
                                            style="cursor: pointer;"
                                            data-bill="{{ json_encode([
                                                'bill_code' => $bill->bill_code,
                                                'from' => $bill->fromCompany->based_in ?? 'N/A',
                                                'to' => $bill->toCompany->based_in ?? 'N/A',
                                                'amount' => number_format($bill->amount, 2),
                                                'description' => $bill->description,
                                                'sender_name' => $bill->sender_name,
                                                'sender_phone' => $bill->sender_phone,
                                                'receiver_name' => $bill->receiver_name,
                                                'receiver_phone' => $bill->receiver_phone,
                                                'media_url' => $bill->media_attachment ? Storage::url($bill->media_attachment) : null,
                                                'proof_url' => $bill->payment_proof_attachment ? Storage::url($bill->payment_proof_attachment) : null,
                                                'is_paid' => $bill->is_paid,
                                                'is_collected' => $bill->is_collected
                                            ]) }}">
                                            <td onclick="event.stopPropagation()">
                                                <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}"
                                                    class="form-check-input bill-checkbox" 
                                                    {{ $bill->checked_by ? 'checked' : '' }}
                                                    {{ !$canEdit ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <strong>{{ $bill->bill_code }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $bill->busDeparture ? \Carbon\Carbon::parse($bill->busDeparture->departure_time)->format('h:i A') : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $bill->fromCompany->based_in ?? 'N/A' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $bill->toCompany->based_in ?? 'N/A' }}</div>
                                            </td>
                                            <td>RM {{ number_format($bill->amount, 2) }}</td>
                                            <td>
                                                @if($bill->checked_by)
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i> {{ $bill->checker->name ?? $bill->checked_by }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                No bills found for this date.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($canEdit)
                        <div class="tm-card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Checklist
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <!-- Checklist Item Detail Modal -->
    <div class="modal fade" id="billDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bill Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 class="mb-0" id="modalBillCode"></h3>
                        <div class="text-muted" id="modalAmount"></div>
                        <div class="mt-2">
                            <span class="badge bg-success d-none" id="modalPaidBadge">Paid</span>
                            <span class="badge bg-danger d-none" id="modalUnpaidBadge">Unpaid</span>
                            <span class="badge bg-primary d-none" id="modalCollectedBadge">Collected</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">From</label>
                            <div class="fw-bold" id="modalFrom"></div>
                            <div class="small" id="modalSender"></div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase">To</label>
                            <div class="fw-bold" id="modalTo"></div>
                            <div class="small" id="modalReceiver"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase">Description</label>
                        <div id="modalDescription" class="p-2 bg-light rounded"></div>
                    </div>

                    <div class="mb-3 d-none" id="modalImageContainer">
                        <label class="small text-muted text-uppercase">Item Image</label>
                        <img src="" id="modalImage" class="img-fluid rounded border w-100" alt="Item Image">
                    </div>

                    <div class="mb-3 d-none" id="modalProofContainer">
                        <label class="small text-muted text-uppercase">Payment Proof</label>
                        <div class="mt-1">
                            <a href="" target="_blank" id="modalProofLink" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-text"></i> View Proof Document
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('checkAll').addEventListener('change', function () {
                var checkboxes = document.querySelectorAll('.bill-checkbox');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = this.checked;
                }, this);
            });

            // Row click handler for details
            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', function(e) {
                    // Prevent triggering if clicked on checkbox
                    if (e.target.closest('.bill-checkbox') || e.target.closest('input')) return;

                    const data = JSON.parse(this.dataset.bill);
                    
                    document.getElementById('modalBillCode').textContent = data.bill_code;
                    document.getElementById('modalAmount').textContent = 'RM ' + data.amount;
                    document.getElementById('modalFrom').textContent = data.from;
                    document.getElementById('modalTo').textContent = data.to;
                    
                    // Sender/Receiver details
                    const senderText = [data.sender_name, data.sender_phone].filter(Boolean).join(' • ');
                    document.getElementById('modalSender').textContent = senderText;

                    const receiverText = [data.receiver_name, data.receiver_phone].filter(Boolean).join(' • ');
                    document.getElementById('modalReceiver').textContent = receiverText;

                    document.getElementById('modalDescription').textContent = data.description || 'No description';

                    // Badges
                    const paidBadge = document.getElementById('modalPaidBadge');
                    const unpaidBadge = document.getElementById('modalUnpaidBadge');
                    if (data.is_paid) {
                        paidBadge.classList.remove('d-none');
                        unpaidBadge.classList.add('d-none');
                    } else {
                        paidBadge.classList.add('d-none');
                        unpaidBadge.classList.remove('d-none');
                    }

                    const collectedBadge = document.getElementById('modalCollectedBadge');
                    if (data.is_collected) {
                        collectedBadge.classList.remove('d-none');
                    } else {
                        collectedBadge.classList.add('d-none');
                    }

                    // Image
                    const imgContainer = document.getElementById('modalImageContainer');
                    const img = document.getElementById('modalImage');
                    if (data.media_url) {
                        img.src = data.media_url;
                        imgContainer.classList.remove('d-none');
                    } else {
                        imgContainer.classList.add('d-none');
                    }

                    // Payment Proof
                    const proofContainer = document.getElementById('modalProofContainer');
                    const proofLink = document.getElementById('modalProofLink');
                    if (data.proof_url) {
                        proofLink.href = data.proof_url;
                        proofContainer.classList.remove('d-none');
                    } else {
                        proofContainer.classList.add('d-none');
                    }

                    // Show Modal
                    var myModal = new bootstrap.Modal(document.getElementById('billDetailModal'));
                    myModal.show();
                });
            });
        </script>
    @endpush
@endsection
