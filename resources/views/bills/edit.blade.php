@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="tm-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.index') }}">Bills</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('bills.show', $bill) }}">{{ $bill->bill_code }}</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit</span>
</div>

<div class="tm-header">
  <div>
    <h2 class="mb-1">Edit Bill {{ $bill->bill_code }}</h2>
    <div class="text-muted">Update bill information</div>
  </div>
</div>

@php
  $payment = is_string($bill->payment_details) ? json_decode($bill->payment_details, true) : $bill->payment_details;
  $customer = is_string($bill->customer_info) ? json_decode($bill->customer_info, true) : $bill->customer_info;
  $sst = is_string($bill->sst_details) ? json_decode($bill->sst_details, true) : $bill->sst_details;
@endphp

<div class="row g-3">
  <div class="col-md-8">
    <div class="tm-card">
      <div class="tm-card-header">
        <i class="bi bi-receipt me-2"></i> Bill Information
      </div>
      <div class="tm-card-body">
        <form method="post" action="{{ route('bills.update', $bill) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-hash"></i> Bill Code <span class="text-danger">*</span>
              </label>
              <input type="text" name="bill_code" class="form-control @error('bill_code') is-invalid @enderror"
                     value="{{ old('bill_code', $bill->bill_code) }}" required>
              <div class="form-text">Unique identifier for this bill</div>
              @error('bill_code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-calendar"></i> Bill Date <span class="text-danger">*</span>
              </label>
              <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                     value="{{ old('date', $bill->date?->format('Y-m-d')) }}" required>
              @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-bus-front"></i> Bus Departure DateTime
              </label>
              <input type="datetime-local" name="bus_datetime" class="form-control @error('bus_datetime') is-invalid @enderror"
                     value="{{ old('bus_datetime', $bill->bus_datetime ? $bill->bus_datetime->format('Y-m-d\TH:i') : '') }}">
              <div class="form-text">Vehicle departure datetime for grouping bills</div>
              @error('bus_datetime')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-cash-stack"></i> Amount (RM) <span class="text-danger">*</span>
              </label>
              <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
                     value="{{ old('amount', $bill->amount) }}" required>
              <div class="form-text">Enter amount in Malaysian Ringgit</div>
              @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-truck"></i> ETA (Estimated Arrival)
              </label>
              <input type="text" name="eta" class="form-control @error('eta') is-invalid @enderror"
                     value="{{ old('eta', $bill->eta) }}" placeholder="e.g., 3-5 business days">
              @error('eta')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label">
                <i class="bi bi-file-text"></i> Description
              </label>
              <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                        placeholder="Additional details about this bill">{{ old('description', $bill->description) }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-credit-card me-2"></i>Payment Details</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-credit-card-2-front"></i> Payment Method
              </label>
              <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                <option value="">Select payment method</option>
                <option value="cash" {{ old('payment_method', $payment['method'] ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ old('payment_method', $payment['method'] ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="e_wallet_qr" {{ old('payment_method', $payment['method'] ?? '') == 'e_wallet_qr' ? 'selected' : '' }}>E-wallet/QR</option>
                <option value="cod" {{ old('payment_method', $payment['method'] ?? '') == 'cod' ? 'selected' : '' }}>COD</option>
              </select>
              @error('payment_method')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-calendar-check"></i> Payment Date
              </label>
              <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                     value="{{ old('payment_date', $payment['date'] ?? '') }}">
              @error('payment_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-receipt me-2"></i>Payment Proof (QR, Bank Transfer)</h5>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">
                <i class="bi bi-paperclip"></i> Upload Payment Proof
              </label>
              <input type="file" name="payment_proof_attachment" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,application/pdf" class="form-control @error('payment_proof_attachment') is-invalid @enderror">
              <div class="form-text">Upload receipt/transfer slip (JPG, PNG, GIF, WEBP, PDF; max 5MB)</div>
              @error('payment_proof_attachment')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-info-circle me-2"></i>Bill Status & Tracking</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-check-circle"></i> Payment Status
              </label>
              <select name="is_paid" class="form-select @error('is_paid') is-invalid @enderror">
                <option value="0" {{ old('is_paid', $bill->is_paid ? '1' : '0') == '0' ? 'selected' : '' }}>Unpaid</option>
                <option value="1" {{ old('is_paid', $bill->is_paid ? '1' : '0') == '1' ? 'selected' : '' }}>Paid</option>
              </select>
              @error('is_paid')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-check"></i> Checked By
              </label>
              <select name="checked_by" class="form-select @error('checked_by') is-invalid @enderror">
                <option value="">Not checked yet</option>
                @foreach($users ?? [] as $user)
                  <option value="{{ $user->id }}" {{ old('checked_by', $bill->checked_by) == $user->id ? 'selected' : '' }}>
                    {{ $user->name }} ({{ $user->role }})
                  </option>
                @endforeach
              </select>
              <div class="form-text">Select who checked this bill (staff can update this)</div>
              @error('checked_by')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div> --}}

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-plus"></i> Created By
              </label>
              <input type="text" class="form-control" value="{{ $bill->creator->name ?? 'N/A' }} ({{ $bill->creator->role ?? 'N/A' }})" disabled>
              <div class="form-text">Original creator of this bill</div>
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-arrow-left-right me-2"></i>Company-to-Company Routing</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-building"></i> From Company
              </label>
              <select name="from_company_id" class="form-select @error('from_company_id') is-invalid @enderror">
                <option value="">Select origin company</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}" {{ old('from_company_id', $bill->from_company_id) == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                  </option>
                @endforeach
              </select>
              @error('from_company_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-building-fill"></i> To Company
              </label>
              <select name="to_company_id" class="form-select @error('to_company_id') is-invalid @enderror">
                <option value="">Select destination company</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}" {{ old('to_company_id', $bill->to_company_id) == $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                  </option>
                @endforeach
              </select>
              @error('to_company_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person"></i> Sender Name
              </label>
              <input type="text" name="sender_name" class="form-control @error('sender_name') is-invalid @enderror"
                     value="{{ old('sender_name', $bill->sender_name) }}" placeholder="Person sending the package">
              @error('sender_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-telephone"></i> Sender Phone
              </label>
              <input type="text" name="sender_phone" class="form-control @error('sender_phone') is-invalid @enderror"
                     value="{{ old('sender_phone', $bill->sender_phone) }}" placeholder="+60 12-345 6789">
              @error('sender_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-fill"></i> Receiver Name
              </label>
              <input type="text" name="receiver_name" class="form-control @error('receiver_name') is-invalid @enderror"
                     value="{{ old('receiver_name', $bill->receiver_name) }}" placeholder="Person receiving the package">
              @error('receiver_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-telephone-fill"></i> Receiver Phone
              </label>
              <input type="text" name="receiver_phone" class="form-control @error('receiver_phone') is-invalid @enderror"
                     value="{{ old('receiver_phone', $bill->receiver_phone) }}" placeholder="+60 12-345 6789">
              @error('receiver_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-person me-2"></i>Customer Information</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-badge"></i> Customer Name
              </label>
              <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                     value="{{ old('customer_name', $customer['name'] ?? '') }}">
              @error('customer_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-telephone"></i> Customer Contact
              </label>
              <input type="text" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror"
                     value="{{ old('customer_phone', $customer['phone'] ?? '') }}" placeholder="+60 12-345 6789">
              @error('customer_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-person-vcard"></i> Customer IC Number
              </label>
              <input type="text" name="customer_ic_number" class="form-control @error('customer_ic_number') is-invalid @enderror"
                     value="{{ old('customer_ic_number', $bill->customer_ic_number ?? ($customer['ic'] ?? '')) }}" placeholder="e.g., 910101-01-1234">
              @error('customer_ic_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label">
                <i class="bi bi-geo-alt"></i> Customer Address
              </label>
              <textarea name="customer_address" rows="2" class="form-control @error('customer_address') is-invalid @enderror"
                        placeholder="Complete delivery address">{{ old('customer_address', $customer['address'] ?? '') }}</textarea>
              @error('customer_address')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-building me-2"></i>Company</h5>

          <div class="row g-3">
            @if(auth()->user()->role === 'admin')
              {{-- Admin: Hide company field, use hidden input with their company_id --}}
              <input type="hidden" name="company_id" id="company_id" value="{{ auth()->user()->company_id }}">
              <div class="col-md-6">
                <label class="form-label">
                  <i class="bi bi-building"></i> Company
                </label>
                <input type="text" class="form-control" value="{{ auth()->user()->company->name ?? 'N/A' }}" disabled>
                <div class="form-text">Your assigned company</div>
              </div>
            @else
              {{-- Super Admin: Show company selection --}}
              <div class="col-md-6">
                <label class="form-label">
                  <i class="bi bi-building"></i> Company <span class="text-danger">*</span>
                </label>
                <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                  <option value="">Select company</option>
                  @foreach($companies as $company)
                    <option value="{{ $company->id }}" {{ old('company_id', $bill->company_id) == $company->id ? 'selected' : '' }}>
                      {{ $company->name }}
                    </option>
                  @endforeach
                </select>
                @error('company_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            @endif

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-shield-check"></i> Courier Policy
              </label>
              <select name="courier_policy_id" id="courier_policy_id" class="form-select @error('courier_policy_id') is-invalid @enderror">
                <option value="">Select courier policy</option>
                @foreach($policies as $policy)
                  <option value="{{ $policy->id }}"
                          data-company-id="{{ $policy->company_id }}"
                          {{ old('courier_policy_id', $bill->courier_policy_id) == $policy->id ? 'selected' : '' }}>
                    {{ $policy->name }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">Select a courier policy for this company</div>
              @error('courier_policy_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-receipt-cutoff me-2"></i>SST (Sales & Service Tax)</h5>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-percent"></i> SST Rate (%)
              </label>
              <input type="number" step="0.01" name="sst_rate" class="form-control @error('sst_rate') is-invalid @enderror"
                     value="{{ old('sst_rate', $sst['rate'] ?? '0') }}" placeholder="e.g., 6">
              <div class="form-text">Enter SST percentage rate</div>
              @error('sst_rate')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-currency-dollar"></i> SST Amount (RM)
              </label>
              <input type="number" step="0.01" name="sst_amount" class="form-control @error('sst_amount') is-invalid @enderror"
                     value="{{ old('sst_amount', $sst['amount'] ?? '0') }}" placeholder="0.00">
              <div class="form-text">Calculated SST amount</div>
              @error('sst_amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">
          <h5 class="mb-3"><i class="bi bi-paperclip me-2"></i>Media Attachment</h5>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">
                <i class="bi bi-image"></i> Upload Image
              </label>
              <input type="file" name="media_attachment" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="form-control @error('media_attachment') is-invalid @enderror">
              <div class="form-text">Upload a single image file (max 5MB). Accepted formats: JPG, PNG, GIF, WEBP</div>
              @if($bill->media_attachment)
                <div class="mt-2">
                  <small class="text-muted">Current attachment: </small>
                  <a href="{{ Storage::url($bill->media_attachment) }}" target="_blank" class="text-primary">
                    <i class="bi bi-image"></i> View Current Image
                  </a>
                </div>
              @endif
              @error('media_attachment')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg"></i> Update Bill
            </button>
            <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="tm-card">
      <div class="tm-card-header">
        <i class="bi bi-clock-history me-2"></i> History
      </div>
      <div class="tm-card-body">
        <div class="mb-3">
          <div class="text-muted small mb-1">Created</div>
          <div class="small">{{ $bill->created_at->format('M d, Y h:i A') }}</div>
        </div>
        <div>
          <div class="text-muted small mb-1">Last Updated</div>
          <div class="small">{{ $bill->updated_at->format('M d, Y h:i A') }}</div>
        </div>
      </div>
    </div>

    <div class="tm-card mt-3">
      <div class="tm-card-header">
        <i class="bi bi-lightbulb me-2"></i> Tips
      </div>
      <div class="tm-card-body">
        <ul class="small mb-0 ps-3">
          <li class="mb-2">Bill code must remain unique</li>
          <li class="mb-2">Payment details help track transaction status</li>
          <li class="mb-2">Update customer info if delivery details change</li>
          <li>Attach updated documents as needed</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const companySelect = document.getElementById('company_id');
    const policySelect = document.getElementById('courier_policy_id');

    if (!companySelect || !policySelect) return;

    function filterPolicies() {
      // Get company ID - works for both select and hidden input
      const companyId = companySelect.value || companySelect.getAttribute('value');
      if (!companyId) return;

      [...policySelect.options].forEach((opt) => {
        if (!opt.value) return; // skip placeholder
        const cid = opt.getAttribute('data-company-id');
        opt.hidden = companyId && cid !== companyId;
      });
      // If selected option hidden, reset
      const selected = policySelect.selectedOptions[0];
      if (selected && selected.hidden) {
        policySelect.value = '';
      }
    }

    // Only add change listener if it's a select element (super admin)
    if (companySelect.tagName === 'SELECT') {
      companySelect.addEventListener('change', filterPolicies);
    }

    // Initial filter (works for both admin and super admin)
    filterPolicies();
  })();
  </script>
@endpush
