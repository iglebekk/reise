@props(['trip'])

@can('invite', $trip)
    <section class="mb-4" aria-labelledby="invitations-heading">
        <header class="mb-3">
            <h2 id="invitations-heading" class="h4 mb-1">{{ __('trips.invitations') }}</h2>
            <p class="text-body-secondary mb-0">{{ __('trips.invitations_description') }}</p>
        </header>

        @if (session('trip_status') === 'created')
            <x-app.alert>{{ __('trips.invitation_created') }}</x-app.alert>
        @elseif (session('trip_status') === 'revoked')
            <x-app.alert>{{ __('trips.invitation_revoked') }}</x-app.alert>
        @endif

        <section class="card mb-3" aria-labelledby="create-invitation-heading">
            <div class="card-body">
                <h3 id="create-invitation-heading" class="h5">{{ __('trips.invite_member') }}</h3>
                <x-form.form :action="route('trips.invitations.store', $trip)">
                    <div class="row align-items-end">
                        <div class="col-12 col-md-6">
                            <x-form.field name="email" label="trips.invitation_email" type="email" required />
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-field mb-3">
                                <label for="invitation_role" class="form-label">{{ __('trips.invitation_role') }}</label>
                                <select id="invitation_role" name="role" class="form-select" required>
                                    <option value="member" @selected(old('role', 'member') === 'member')>{{ __('trips.roles.member') }}</option>
                                    <option value="admin" @selected(old('role') === 'admin')>{{ __('trips.roles.admin') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-2 mb-3">
                            <button class="btn btn-primary w-100" type="submit">{{ __('trips.send_invitation') }}</button>
                        </div>
                    </div>
                </x-form.form>
            </div>
        </section>

        <section class="card" aria-labelledby="invitation-list-heading">
            <div class="card-body">
                <h3 id="invitation-list-heading" class="h5">{{ __('trips.invitation_list') }}</h3>
                @if ($trip->invitations->isEmpty())
                    <p class="text-body-secondary mb-0">{{ __('trips.no_invitations') }}</p>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($trip->invitations as $invitation)
                            <div class="list-group-item px-0 d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div>
                                    <p class="fw-semibold mb-1">{{ $invitation->email }}</p>
                                    <p class="text-body-secondary mb-1">{{ __('trips.roles.'.$invitation->role) }} · {{ __('trips.invitation_expires', ['date' => $invitation->expires_at->isoFormat('lll')]) }}</p>
                                    @if ($invitation->accepted_at)
                                        <span class="badge text-bg-success">{{ __('trips.invitation_accepted') }}</span>
                                    @elseif ($invitation->expires_at->isPast())
                                        <span class="badge text-bg-secondary">{{ __('trips.invitation_expired') }}</span>
                                    @else
                                        <span class="badge text-bg-warning">{{ __('trips.invitation_pending') }}</span>
                                    @endif
                                </div>
                                @if (! $invitation->accepted_at && $invitation->expires_at->isFuture())
                                    <x-form.form :action="route('trips.invitations.destroy', [$trip, $invitation])" method="DELETE" class="align-self-md-center">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">{{ __('trips.revoke_invitation') }}</button>
                                    </x-form.form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </section>
@endcan
