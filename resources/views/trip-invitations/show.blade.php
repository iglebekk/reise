<x-app.authenticated-layout :title="__('trips.invitation_title')">
    <x-app.trip-invitation :invitation="$invitation" :token="$token" />
</x-app.authenticated-layout>
