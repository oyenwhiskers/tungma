@extends('layouts.app')

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('customers.index') }}">Customers</a>
    <i class="bi bi-chevron-right"></i>
    <span>Add Customer</span>
</div>

<div class="tm-header">
    <h2 class="mb-1">Add New Customer</h2>
    <div class="text-muted">Register a new customer or company profile.</div>
</div>

<div class="tm-card" style="max-width: 600px;">
    <div class="tm-card-body">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label fw-bold">Customer / Company Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Type the name and the Debtor Code will auto-generate.</div>
            </div>

            <div class="mb-4">
                <label for="contact_number" class="form-label fw-bold">Contact Number</label>
                <input type="text" class="form-control @error('contact_number') is-invalid @enderror" id="contact_number" name="contact_number" value="{{ old('contact_number') }}">
                @error('contact_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="debtor_code" class="form-label fw-bold">Debtor Code <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control @error('debtor_code') is-invalid @enderror" id="debtor_code" name="debtor_code" value="{{ old('debtor_code') }}" required>
                    <button class="btn btn-outline-secondary" type="button" id="btn-generate">Generate</button>
                </div>
                @error('debtor_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">You can manually edit this code if needed. Must start with 300-</div>
            </div>

            @if(auth()->user()->role === 'super_admin')
            <div class="mb-4">
                <label class="form-label fw-bold">Assign to Company <span class="text-danger">*</span></label>
                <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">This customer will only be visible to this company.</div>
                @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @else
            <div class="mb-4">
                <label class="form-label fw-bold">Company</label>
                <input type="text" class="form-control bg-light" value="{{ auth()->user()->company?->name ?? 'Global / N/A' }}" disabled>
            </div>
            @endif

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('debtor_code');
        const btnGenerate = document.getElementById('btn-generate');

        let typingTimer;

        // Auto-generate on stop typing
        nameInput.addEventListener('keyup', function() {
            clearTimeout(typingTimer);
            if (nameInput.value && !codeInput.value) { // only auto-generate if empty
                typingTimer = setTimeout(generateCode, 800);
            }
        });

        // Manual generate button click
        btnGenerate.addEventListener('click', function() {
            generateCode();
        });

        function generateCode() {
            const name = nameInput.value;
            if (!name) return;

            btnGenerate.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btnGenerate.disabled = true;

            fetch(`{{ route('customers.generateCode') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name: name })
            })
            .then(response => response.json())
            .then(data => {
                if(data.code) {
                    codeInput.value = data.code;
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                btnGenerate.innerHTML = 'Generate';
                btnGenerate.disabled = false;
            });
        }
    });
</script>
@endpush
