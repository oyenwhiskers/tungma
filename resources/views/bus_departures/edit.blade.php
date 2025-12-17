@extends('layouts.app')

@section('content')
    <div class="tm-header">
        <div>
            <h2>Edit Departure</h2>
            <div class="text-muted">Update bus departure schedule.</div>
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
                    <form action="{{ route('bus-departures.update', $busDeparture->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="departure_time" class="form-label">Departure Time</label>
                            <input type="time" class="form-control" id="departure_time" name="departure_time"
                                value="{{ $busDeparture->departure_time }}" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update Departure</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection