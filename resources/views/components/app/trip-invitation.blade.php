@props(['invitation', 'token'])

<x-app.page-header :title="__('trips.invitation_title')" :description="__('trips.invitation_description')" />

<section class="card" aria-labelledby="invitation-trip-heading">
    <div class="card-body p-4">
        <h2 id="invitation-trip-heading" class="h4">{{ $invitation->trip->destination }}</h2>
        <p>{{ __('trips.invited_email', ['email' => $invitation->email]) }}</p>
        <p class="text-body-secondary">{{ __('trips.invitation_expires', ['date' => $invitation->expires_at->isoFormat('lll')]) }}</p>
        @error('token')
            <x-app.alert variant="danger" role="alert">{{ $message }}</x-app.alert>
        @enderror
        <x-form.form :action="route('trip-invitations.accept')">
            <input type="hidden" name="token" value="{{ $token }}">
            <button class="btn btn-primary" type="submit">{{ __('trips.accept_invitation') }}</button>
        </x-form.form>
    </div>
</section>
