<x-app.authenticated-layout :title="__('trips.index_title')">
    <x-app.trip-index-content :trips="$trips" />
</x-app.authenticated-layout>
