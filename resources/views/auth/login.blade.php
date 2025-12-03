@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height:calc(100vh - 200px);">
  <div class="tm-card" style="width:460px; box-shadow:0 10px 40px rgba(0,0,0,0.08);">
    <div class="tm-card-body p-4">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center" style="width:60px; height:60px; background:linear-gradient(135deg, var(--tm-accent), var(--tm-primary)); border-radius:16px; margin-bottom:16px;">
          <i class="bi bi-truck text-white" style="font-size:28px;"></i>
        </div>
        <h3 class="mb-2">Welcome Back</h3>
        <div class="text-muted">Sign in to TungMa Management System</div>
      </div>
      
      <form method="post" action="{{ url('/login') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-envelope text-muted"></i>
            </span>
            <input type="email" name="email" class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="your@email.com" required autofocus>
          </div>
          @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
        
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-lock text-muted"></i>
            </span>
            <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Enter your password" required>
          </div>
        </div>
        
        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" name="remember" id="remember">
          <label class="form-check-label small" for="remember">
            Remember me for 30 days
          </label>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2">
          <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
        </button>
      </form>
      
      <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted">
          <i class="bi bi-shield-check me-1"></i> Secure Login
        </small>
      </div>
    </div>
  </div>
</div>

<style>
.input-group-text { border-radius:8px 0 0 8px; }
.input-group .form-control { border-radius:0 8px 8px 0; }
.input-group .form-control:focus { box-shadow:none; border-color:var(--tm-primary); }
.input-group .form-control:focus + .input-group-text { border-color:var(--tm-primary); }
</style>
@endsection
