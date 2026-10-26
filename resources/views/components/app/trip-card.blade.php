@props(['trip'])

<a class="card h-100 text-decoration-none text-body" href="{{ route('trips.show', $trip) }}">
    <div class="card-body">
        <h2 class="h5 card-title">{{ $trip->destination }}</h2>
        <p class="card-text text-body-secondary mb-2">
            {{ $trip->start_date->isoFormat('ll') }} – {{ $trip->end_date->isoFormat('ll') }}
        </p>
        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ __('trips.types.'.$trip->type) }}</span>
    </div>
</a>
