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
          <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#clearStorageModal" data-target="{{ $area }}" data-label="{{ ucfirst(str_replace('_', ' ', $area)) }}">
            Clear
          </button>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>

{{-- Password Confirmation Modal for Storage Clearing --}}
<div class="modal fade" id="clearStorageModal" tabindex="-1" aria-labelledby="clearStorageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearStorageModalLabel">Confirm Storage Clearing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="clearStorageForm" method="POST" action="{{ route('storage.clear') }}">
                @csrf
                <input type="hidden" name="target" id="modalTarget">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span id="modalWarning">Are you sure you want to clear this storage area? This action cannot be undone.</span>
                    </div>
                    <p class="mb-3">Please enter your password to confirm this action:</p>
                    <div class="mb-3">
                        <label for="modalPassword" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="modalPassword" name="password" required autocomplete="current-password" autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Clear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('clearStorageModal');
    const form = document.getElementById('clearStorageForm');
    const targetInput = document.getElementById('modalTarget');
    const warningText = document.getElementById('modalWarning');
    const passwordInput = document.getElementById('modalPassword');
    
    // Check if there's a password error and show modal
    @if($errors->has('password'))
        const target = '{{ old("target") }}';
        if (target) {
            targetInput.value = target;
            const label = target.charAt(0).toUpperCase() + target.slice(1).replace(/_/g, ' ');
            warningText.textContent = `Are you sure you want to clear ${label}? This action cannot be undone.`;
            passwordInput.classList.add('is-invalid');
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    @endif
    
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            const target = button.getAttribute('data-target');
            const label = button.getAttribute('data-label');
            
            targetInput.value = target;
            warningText.textContent = `Are you sure you want to clear ${label}? This action cannot be undone.`;
            passwordInput.value = '';
            passwordInput.classList.remove('is-invalid');
        }
    });
    
    // Clear password field when modal is hidden
    modal.addEventListener('hidden.bs.modal', function() {
        passwordInput.value = '';
        passwordInput.classList.remove('is-invalid');
    });
});
</script>
@endsection
