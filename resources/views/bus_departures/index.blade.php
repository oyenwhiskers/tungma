@extends('layouts.app')

@section('content')
    <div class="tm-header">
        <div>
            <h2>Bus Departures</h2>
            <div class="text-muted">Manage bus departure times.</div>
        </div>
        <div>
            <a href="{{ route('bus-departures.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New
            </a>
        </div>
    </div>

    <div class="tm-card tm-table">
        <div class="tm-card-header">All Departures</div>
        <div class="tm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Departure Time</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($busDepartures as $departure)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($departure->departure_time)->format('g:i A') }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('bus-departures.edit', $departure->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('bus-departures.destroy', $departure->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-4 text-muted">
                                    No departures found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection