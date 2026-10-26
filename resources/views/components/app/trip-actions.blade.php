@props(['trip'])

@if (session('trip_status') === 'updated')
    <x-app.alert>{{ __('trips.updated') }}</x-app.alert>
@endif

<div class="d-flex flex-column flex-sm-row gap-2 mb-4">
    @can('update', $trip)
        <a class="btn btn-outline-primary" href="{{ route('trips.edit', $trip) }}">{{ __('trips.edit_trip') }}</a>
    @endcan
    @can('delete', $trip)
        <x-form.form :action="route('trips.destroy', $trip)" method="DELETE">
            <button class="btn btn-outline-danger" type="submit">{{ __('trips.delete_trip') }}</button>
        </x-form.form>
    @endcan
</div>
