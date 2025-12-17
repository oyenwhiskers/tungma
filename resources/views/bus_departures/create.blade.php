@extends('layouts.app')

@section('content')
    <div class="tm-header">
        <div>
            <h2>Add Departure</h2>
            <div class="text-muted">Create a new bus departure schedule.</div>
        </div>
        <div>
            <a href="{{ route('bus-departures.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="tm-card">
                <div class="tm-card-body">
                    <form action="{{ route('bus-departures.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="departure_time" class="form-label">Departure Time</label>
                            <input type="time" class="form-control" id="departure_time" name="departure_time" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Create Departure</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection