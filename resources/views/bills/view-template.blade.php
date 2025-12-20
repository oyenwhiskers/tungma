@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.index') }}">Bills</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.show', $bill) }}">{{ $bill->bill_code }}</a>
    <i class="bi bi-chevron-right"></i>
    <span>View Receipt</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Bill Receipt - {{ $bill->bill_code }}</h2>
    <div class="text-muted">View and download receipt copies</div>
  </div>
  <div>
    <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>
</div>

<div class="tm-card">
    <div class="tm-card-body">
        <!-- Copy Type Buttons -->
        <div class="d-flex gap-2 mb-4 justify-content-center flex-wrap">
            <a href="{{ route('bills.template', ['bill' => $bill->id, 'copy' => 'customer']) }}" 
               class="btn btn-primary btn-lg view-btn active" 
               id="btn-customer"
               data-copy="customer"
               target="pdf-frame">
                <i class="bi bi-person"></i> Customer Copy
            </a>
            <a href="{{ route('bills.template', ['bill' => $bill->id, 'copy' => 'office']) }}" 
               class="btn btn-outline-success btn-lg view-btn" 
               id="btn-office"
               data-copy="office"
               target="pdf-frame">
                <i class="bi bi-building"></i> Office & Receiver Copy
            </a>
            <div class="vr"></div>
            <a href="{{ route('bills.template', ['bill' => $bill->id, 'copy' => 'customer', 'download' => 1]) }}" 
               class="btn btn-outline-primary btn-lg">
                <i class="bi bi-download"></i> Download Customer Copy
            </a>
            <a href="{{ route('bills.template', ['bill' => $bill->id, 'copy' => 'office', 'download' => 1]) }}" 
               class="btn btn-outline-success btn-lg">
                <i class="bi bi-download"></i> Download Office Copy
            </a>
        </div>

        <!-- PDF Viewer -->
        <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; min-height: 800px;">
            <iframe 
                name="pdf-frame" 
                id="pdf-frame"
                src="{{ route('bills.template', ['bill' => $bill->id, 'copy' => 'customer']) }}"
                style="width: 100%; height: 800px; border: none;">
            </iframe>
        </div>
    </div>
</div>

<style>
.btn-lg {
    padding: 12px 24px;
    font-size: 16px;
}

#pdf-frame {
    display: block;
}

.view-btn.active {
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.vr {
    width: 1px;
    background-color: #dee2e6;
    align-self: stretch;
    margin: 0 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle button clicks
    const buttons = document.querySelectorAll('.view-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class and restore original classes
            buttons.forEach(b => {
                b.classList.remove('active');
                if (b.id === 'btn-customer') {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-primary');
                } else if (b.id === 'btn-office') {
                    b.classList.remove('btn-success');
                    b.classList.add('btn-outline-success');
                }
            });
            
            // Add active class and update button style
            this.classList.add('active');
            if (this.id === 'btn-customer') {
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');
            } else if (this.id === 'btn-office') {
                this.classList.remove('btn-outline-success');
                this.classList.add('btn-success');
            }
        });
    });
});
</script>
@endsection

