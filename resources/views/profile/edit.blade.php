@extends('layouts.app')

@section('content')
<h2>Edit Profile</h2>
<form method="post" action="{{ route('profile.update') }}" class="mt-3">
  @csrf
  @method('PUT')
  <div class="mb-2"><label class="form-label">Full Name</label><input class="form-control" name="name" value="{{ auth()->user()->name }}" required></div>
  <div class="mb-2"><label class="form-label">Username</label><input class="form-control" name="username" value="{{ auth()->user()->username }}"></div>
  <div class="mb-2"><label class="form-label">Contact Number</label><input class="form-control" name="contact_number" value="{{ auth()->user()->contact_number }}"></div>
  <div class="mb-2"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="{{ auth()->user()->date_of_birth }}"></div>
  <div class="mb-2"><label class="form-label">Gender</label><input class="form-control" name="gender" value="{{ auth()->user()->gender }}"></div>
  <div class="mb-2"><label class="form-label">IC Number</label><input class="form-control" name="ic_number" value="{{ auth()->user()->ic_number }}"></div>
  <div class="mb-2"><label class="form-label">Position</label><input class="form-control" name="position" value="{{ auth()->user()->position }}"></div>
  <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ auth()->user()->email }}"></div>
  <button class="btn btn-primary">Save</button>
</form>

<hr class="my-4">

<h3>Change Password</h3>
<form method="post" action="{{ route('profile.changePassword') }}" class="mt-3">
  @csrf
  <div class="mb-2"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
  <div class="mb-2"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
  <div class="mb-2"><label class="form-label">Confirm New Password</label><input type="password" class="form-control" name="new_password_confirmation" required></div>
  <button class="btn btn-warning">Change Password</button>
</form>
@endsection
