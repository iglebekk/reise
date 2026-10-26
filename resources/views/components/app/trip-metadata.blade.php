@props(['trip'])

<section class="card mb-4" aria-labelledby="trip-details-heading">
    <div class="card-body">
        <h2 id="trip-details-heading" class="h5 mb-3">{{ __('trips.details') }}</h2>
        <dl class="row mb-0">
            <dt class="col-sm-4">{{ __('trips.destination') }}</dt>
            <dd class="col-sm-8">{{ $trip->destination }}</dd>
            <dt class="col-sm-4">{{ __('trips.dates') }}</dt>
            <dd class="col-sm-8">{{ $trip->start_date->isoFormat('ll') }} – {{ $trip->end_date->isoFormat('ll') }}</dd>
            <dt class="col-sm-4">{{ __('trips.duration') }}</dt>
            <dd class="col-sm-8">{{ trans_choice('trips.days', $trip->durationInDays(), ['count' => $trip->durationInDays()]) }}</dd>
            <dt class="col-sm-4">{{ __('trips.type') }}</dt>
            <dd class="col-sm-8">{{ __('trips.types.'.$trip->type) }}</dd>
            <dt class="col-sm-4">{{ __('trips.transport') }}</dt>
            <dd class="col-sm-8">{{ __('trips.transport_options.'.$trip->transport) }}</dd>
            <dt class="col-sm-4">{{ __('trips.accommodation') }}</dt>
            <dd class="col-sm-8">{{ __('trips.accommodation_options.'.$trip->accommodation) }}</dd>
            <dt class="col-sm-4">{{ __('trips.activities') }}</dt>
            <dd class="col-sm-8">
                @forelse ($trip->activities ?? [] as $activity)
                    {{ __('trips.activity_options.'.$activity) }}@if (! $loop->last), @endif
                @empty
                    {{ __('trips.none') }}
                @endforelse
            </dd>
            @if ($trip->description)
                <dt class="col-sm-4">{{ __('trips.description') }}</dt>
                <dd class="col-sm-8 text-break">{{ $trip->description }}</dd>
            @endif
        </dl>
    </div>
</section>
