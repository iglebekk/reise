@props(['trip'])

<x-app.page-header :title="$trip->destination" :description="__('trips.show_description')" />
<x-app.trip-actions :trip="$trip" />
<x-app.trip-metadata :trip="$trip" />
<x-app.trip-participants :trip="$trip" :members="$trip->members" />
<x-app.trip-members :trip="$trip" :members="$trip->members" />
<x-app.trip-packing-lists :trip="$trip" />
<x-app.trip-invitations :trip="$trip" />
<a class="btn btn-outline-secondary" href="{{ route('trips.index') }}">{{ __('trips.back_to_trips') }}</a>
