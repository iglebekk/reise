@props(['trips'])

<x-app.page-header
    :title="__('trips.index_title')"
    :description="__('trips.index_description')"
    :action="route('trips.create')"
    :action-label="__('trips.create_trip')"
/>

@if (session('trip_status') === 'deleted')
    <x-app.alert>{{ __('trips.deleted') }}</x-app.alert>
@endif

@if ($trips->isEmpty())
    <section class="card">
        <div class="card-body p-4 p-md-5 text-center">
            <h2 class="h4">{{ __('trips.empty_title') }}</h2>
            <p class="text-body-secondary">{{ __('trips.empty_description') }}</p>
            <a class="btn btn-primary" href="{{ route('trips.create') }}">{{ __('trips.plan_first_trip') }}</a>
        </div>
    </section>
@else
    <div class="row g-3 g-md-4">
        @foreach ($trips as $trip)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-app.trip-card :trip="$trip" />
            </div>
        @endforeach
    </div>
@endif
