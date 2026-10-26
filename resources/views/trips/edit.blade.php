<x-app.authenticated-layout :title="__('trips.edit_trip')">
    <x-app.trip-edit-form
        :trip="$trip"
        :trip-types="$tripTypes"
        :transport-options="$transportOptions"
        :accommodation-options="$accommodationOptions"
        :activity-options="$activityOptions"
    />
</x-app.authenticated-layout>
