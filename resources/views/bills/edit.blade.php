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
                <i class="bi bi-bus-front"></i> Bus Departure Time
              </label>
              <select name="bus_departures_id" class="form-select @error('bus_departures_id') is-invalid @enderror">
                <option value="">Select departure time (optional)</option>
                @foreach($busDepartures as $departure)
                  <option value="{{ $departure->id }}" {{ old('bus_departures_id', $bill->bus_departures_id) == $departure->id ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::parse($departure->departure_time)->format('h:i A') }}
                    @if(auth()->user()->role !== 'admin' && $departure->company)
                      - {{ $departure->company->name }}
                    @endif
                  </option>
                @endforeach
              </select>
              <div class="form-text">Vehicle departure time for grouping bills</div>
              @error('bus_departures_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>



            <div class="col-12">
              <label class="form-label">
                <i class="bi bi-file-text"></i> Description (Products)
              </label>
              <div id="products-container">
                <div class="product-item mb-3 p-3 border rounded" data-index="0">
                  <div class="row g-2">
                    <div class="col-md-4">
                      <label class="form-label small">Product</label>
                      <select name="products[0][product]" class="form-select product-select">
                        <option value="">Select product</option>
                        <option value="Plastik">Plastik</option>
                        <option value="Kotak">Kotak</option>
                        <option value="Karung">Karung</option>
                        <option value="Spring">Spring</option>
                        <option value="Sampul">Sampul</option>
                        <option value="Bag">Bag</option>
                        <option value="Bungkusan">Bungkusan</option>
                        <option value="Gabus">Gabus</option>
                        <option value="Roll">Roll</option>
                        <option value="Besi">Besi</option>
                        <option value="Battery">Battery</option>
                        <option value="Tayar">Tayar</option>
                        <option value="Kiriman Duit">Kiriman Duit</option>
                        <option value="Tong">Tong</option>
                        <option value="__OTHER__">Other</option>
                      </select>
                      <input type="text" name="products[0][product_other]" class="form-control mt-2 product-other-input" placeholder="Enter custom product name" style="display: none;">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">Quantity</label>
                      <input type="number" name="products[0][quantity]" class="form-control product-quantity" min="1" step="1" placeholder="Qty">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">Price (RM)</label>
                      <input type="number" name="products[0][price]" class="form-control product-price" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                      <button type="button" class="btn btn-sm btn-danger remove-product" style="display: none;">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-product">
                <i class="bi bi-plus-circle"></i> Add Product
              </button>
              <input type="hidden" name="description" id="description-input" value="{{ old('description', $bill->description) }}">
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-percent"></i> SST Rate (%)
              </label>
              <input type="number" step="0.01" name="sst_rate" id="sst_rate" class="form-control @error('sst_rate') is-invalid @enderror"
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
              <input type="number" step="0.01" name="sst_amount" id="sst_amount" class="form-control @error('sst_amount') is-invalid @enderror"
                     value="{{ old('sst_amount', $sst['amount'] ?? '0') }}" placeholder="0.00" readonly style="background-color: #e9ecef;">
              <div class="form-text">Calculated SST amount</div>
              @error('sst_amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <div class="p-3 border rounded bg-light-subtle">
                <label class="form-label h5 text-primary">
                  <i class="bi bi-cash-stack"></i> Total Amount (RM)
                </label>
                <input type="number" step="0.01" name="amount" id="amount-input" class="form-control form-control-lg fs-2 fw-bold text-end @error('amount') is-invalid @enderror"
                       value="{{ old('amount', $bill->amount) }}" required readonly style="background-color: #fff;">
                <div class="form-text text-end">Total Bill Amount (Subtotal + SST)</div>
                @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
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

            <div class="col-md-6">
              <label class="form-label">
                <i class="bi bi-box-arrow-down"></i> Collection Status
              </label>
              <select name="is_collected" class="form-select @error('is_collected') is-invalid @enderror">
                <option value="0" {{ old('is_collected', $bill->is_collected ? '1' : '0') == '0' ? 'selected' : '' }}>Uncollected</option>
                <option value="1" {{ old('is_collected', $bill->is_collected ? '1' : '0') == '1' ? 'selected' : '' }}>Collected</option>
              </select>
              @error('is_collected')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

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

  // Product form management
  (function() {
    const productsContainer = document.getElementById('products-container');
    const addProductBtn = document.getElementById('add-product');
    const descriptionInput = document.getElementById('description-input');
    const sstRateInput = document.getElementById('sst_rate');
    const sstAmountInput = document.getElementById('sst_amount');
    const form = document.querySelector('form');

    if (!productsContainer || !addProductBtn) return;

    const products = [
      'Plastik', 'Kotak', 'Karung', 'Spring', 'Sampul', 'Bag',
      'Bungkusan', 'Gabus', 'Roll', 'Besi', 'Battery', 'Tayar',
      'Kiriman Duit', 'Tong'
    ];

    let productIndex = 1;

    function updateRemoveButtons() {
      const items = productsContainer.querySelectorAll('.product-item');
      items.forEach((item, index) => {
        const removeBtn = item.querySelector('.remove-product');
        if (items.length > 1) {
          removeBtn.style.display = 'block';
        } else {
          removeBtn.style.display = 'none';
        }
      });
    }

    function handleProductSelectChange(selectElement) {
      const item = selectElement.closest('.product-item');
      const otherInput = item.querySelector('.product-other-input');
      
      if (selectElement.value === '__OTHER__') {
        otherInput.style.display = 'block';
        otherInput.required = true;
      } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
      }
    }

    function calculateTotal() {
      const items = productsContainer.querySelectorAll('.product-item');
      let subtotal = 0;

      items.forEach((item) => {
        const quantity = parseFloat(item.querySelector('.product-quantity').value) || 0;
        const price = parseFloat(item.querySelector('.product-price').value) || 0;
        subtotal += quantity * price;
      });

      // Calculate SST
      let sstRate = 0;
      let sstAmount = 0;

      if (sstRateInput) {
        sstRate = parseFloat(sstRateInput.value) || 0;
        // Calculate sstAmount based on rate
        sstAmount = subtotal * (sstRate / 100);
        
        // Update sst amount field
        if (sstAmountInput) {
            sstAmountInput.value = sstAmount.toFixed(2);
        }
      } else if (sstAmountInput) {
         // Fallback if no rate input found
         sstAmount = parseFloat(sstAmountInput.value) || 0;
      }

      const total = subtotal + sstAmount;

      const amountInput = document.getElementById('amount-input');
      if (amountInput) {
        amountInput.value = total.toFixed(2);
      }

      return total;
    }

    if (sstRateInput) {
        sstRateInput.addEventListener('input', calculateTotal);
    }

    function buildDescriptionJSON() {
      const items = productsContainer.querySelectorAll('.product-item');
      const productsArray = [];

      items.forEach((item) => {
        const productSelect = item.querySelector('.product-select');
        const otherInput = item.querySelector('.product-other-input');
        const quantity = item.querySelector('.product-quantity').value;
        const price = item.querySelector('.product-price').value;

        let product = productSelect.value;
        
        // If "Other" is selected, use the custom input value
        if (product === '__OTHER__') {
          product = otherInput.value.trim();
        }

        if (product && quantity && price) {
          productsArray.push({
            product: product,
            quantity: parseInt(quantity),
            price: parseFloat(price)
          });
        }
      });

      return JSON.stringify(productsArray);
    }

    addProductBtn.addEventListener('click', function() {
      const newItem = document.createElement('div');
      newItem.className = 'product-item mb-3 p-3 border rounded';
      newItem.setAttribute('data-index', productIndex);

      let optionsHTML = '<option value="">Select product</option>';
      products.forEach(p => {
        optionsHTML += `<option value="${p}">${p}</option>`;
      });
      optionsHTML += '<option value="__OTHER__">Other</option>';

      newItem.innerHTML = `
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label small">Product</label>
            <select name="products[${productIndex}][product]" class="form-select product-select">
              ${optionsHTML}
            </select>
            <input type="text" name="products[${productIndex}][product_other]" class="form-control mt-2 product-other-input" placeholder="Enter custom product name" style="display: none;">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Quantity</label>
            <input type="number" name="products[${productIndex}][quantity]" class="form-control product-quantity" min="1" step="1" placeholder="Qty">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Price (RM)</label>
            <input type="number" name="products[${productIndex}][price]" class="form-control product-price" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-danger remove-product">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      `;

      productsContainer.appendChild(newItem);
      productIndex++;
      updateRemoveButtons();

      // Add change listener for product select
      const newSelect = newItem.querySelector('.product-select');
      newSelect.addEventListener('change', function() {
        handleProductSelectChange(this);
        updateDescription();
      });

      // Add remove event listener
      newItem.querySelector('.remove-product').addEventListener('click', function() {
        newItem.remove();
        updateRemoveButtons();
        updateDescription();
      });
    });

    // Remove product event delegation
    productsContainer.addEventListener('click', function(e) {
      if (e.target.closest('.remove-product')) {
        const item = e.target.closest('.product-item');
        item.remove();
        updateRemoveButtons();
        updateDescription();
      }
    });

    function updateDescription() {
      descriptionInput.value = buildDescriptionJSON();
      calculateTotal(); // Auto-calculate total when description changes
    }

    // Handle product select changes (including "Other" option)
    productsContainer.addEventListener('change', function(e) {
      if (e.target.classList.contains('product-select')) {
        handleProductSelectChange(e.target);
      }
      updateDescription();
    });
    
    // Update description and total on input changes
    productsContainer.addEventListener('input', function(e) {
      if (e.target.classList.contains('product-quantity') || 
          e.target.classList.contains('product-price') ||
          e.target.classList.contains('product-other-input')) {
        updateDescription();
      }
    });

    // Update description before form submission
    form.addEventListener('submit', function(e) {
      updateDescription();
    });

    // Load existing data if any
    const oldDescription = descriptionInput.value;
    if (oldDescription) {
      try {
        const parsed = JSON.parse(oldDescription);
        if (Array.isArray(parsed) && parsed.length > 0) {
          // Clear first item
          const firstItem = productsContainer.querySelector('.product-item');
          if (firstItem) {
            const firstSelect = firstItem.querySelector('.product-select');
            const firstOtherInput = firstItem.querySelector('.product-other-input');
            const productName = parsed[0].product || '';
            
            // Check if product is in the predefined list
            const isPredefined = products.includes(productName);
            
            if (isPredefined) {
              firstSelect.value = productName;
            } else if (productName) {
              firstSelect.value = '__OTHER__';
              firstOtherInput.value = productName;
              firstOtherInput.style.display = 'block';
            }
            
            firstItem.querySelector('.product-quantity').value = parsed[0].quantity || '';
            firstItem.querySelector('.product-price').value = parsed[0].price || '';
          }

          // Add additional items
          for (let i = 1; i < parsed.length; i++) {
            addProductBtn.click();
            const newItem = productsContainer.querySelectorAll('.product-item')[i];
            if (newItem) {
              const newSelect = newItem.querySelector('.product-select');
              const newOtherInput = newItem.querySelector('.product-other-input');
              const productName = parsed[i].product || '';
              
              // Check if product is in the predefined list
              const isPredefined = products.includes(productName);
              
              if (isPredefined) {
                newSelect.value = productName;
              } else if (productName) {
                newSelect.value = '__OTHER__';
                newOtherInput.value = productName;
                newOtherInput.style.display = 'block';
              }
              
              newItem.querySelector('.product-quantity').value = parsed[i].quantity || '';
              newItem.querySelector('.product-price').value = parsed[i].price || '';
            }
          }
        }
      } catch (e) {
        // If not JSON, it might be old text format - ignore
        console.log('Description is not in JSON format, treating as text');
      }
    }

    // Initialize "Other" inputs for existing items
    productsContainer.querySelectorAll('.product-select').forEach(select => {
      if (select.value === '__OTHER__') {
        handleProductSelectChange(select);
      }
    });

    updateRemoveButtons();
    
    // Calculate initial total if there are existing products
    if (oldDescription) {
      try {
        const parsed = JSON.parse(oldDescription);
        if (Array.isArray(parsed) && parsed.length > 0) {
          calculateTotal();
        }
      } catch (e) {
        // Ignore
      }
    }
  })();
  </script>
@endpush
