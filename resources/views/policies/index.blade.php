@extends('layouts.app')

@section('content')
<div class="tm-header">
    <div>
        <h2 class="mb-1">Courier Policies</h2>
        <div class="text-muted">Define and manage delivery rules</div>
    </div>
    <a href="{{ route('policies.create') }}" class="btn btn-primary">New Policy</a>
</div>

<div class="tm-card tm-table">
    <div class="tm-card-body">
        <table class="table">
            <thead><tr><th>Name</th><th>Company</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($policies as $policy)
                <tr>
                    <td><a href="{{ route('policies.show', $policy) }}">{{ $policy->name }}</a></td>
                    <td>{{ $policy->company?->name }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('policies.show', $policy) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('policies.edit', $policy) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('policies.destroy', $policy) }}" method="POST" onsubmit="return confirm('Delete this policy?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="mt-2">{{ $policies->links() }}</div>
    </div>
</div>
@endsection
