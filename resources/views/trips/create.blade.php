<x-app.authenticated-layout :title="__('trips.create_title')">
    <x-app.trip-create-form
        :trip-types="$tripTypes"
        :transport-options="$transportOptions"
        :accommodation-options="$accommodationOptions"
        :activity-options="$activityOptions"
    />
</x-app.authenticated-layout>
