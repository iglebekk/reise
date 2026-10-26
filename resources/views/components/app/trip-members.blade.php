@props(['trip', 'members'])

<section class="mb-4" aria-labelledby="trip-members-heading">
    <header class="mb-3">
        <h2 id="trip-members-heading" class="h4 mb-1">{{ __('trips.members') }}</h2>
        <p class="text-body-secondary mb-0">{{ __('trips.members_description') }}</p>
    </header>

    @if (session('trip_status') === 'member-role-updated')
        <x-app.alert>{{ __('trips.member_role_updated') }}</x-app.alert>
    @elseif (session('trip_status') === 'member-removed')
        <x-app.alert>{{ __('trips.member_removed') }}</x-app.alert>
    @endif

    <div class="card">
        <div class="list-group list-group-flush">
            @foreach ($members as $member)
                <div class="list-group-item d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 py-3">
                    <div>
                        <p class="fw-semibold mb-1">{{ $member->name }}</p>
                        <p class="text-body-secondary mb-0">{{ __('trips.roles.'.$member->pivot->role) }}</p>
                    </div>

                    @can('invite', $trip)
                        <div class="d-flex flex-column flex-sm-row align-items-sm-end gap-2">
                            <x-form.form :action="route('trips.members.update', [$trip, $member])" method="PATCH" class="d-flex flex-column flex-sm-row align-items-sm-end gap-2">
                                <div class="form-field">
                                    <label for="member_role_{{ $member->id }}" class="form-label">{{ __('trips.member_role', ['name' => $member->name]) }}</label>
                                    <select id="member_role_{{ $member->id }}" name="role" class="form-select">
                                        <option value="member" @selected($member->pivot->role === 'member')>{{ __('trips.roles.member') }}</option>
                                        <option value="admin" @selected($member->pivot->role === 'admin')>{{ __('trips.roles.admin') }}</option>
                                    </select>
                                </div>
                                <button class="btn btn-outline-primary" type="submit">{{ __('trips.update_member_role') }}</button>
                            </x-form.form>
                            <x-form.form :action="route('trips.members.destroy', [$trip, $member])" method="DELETE">
                                <button class="btn btn-outline-danger" type="submit">{{ __('trips.remove_member') }}</button>
                            </x-form.form>
                        </div>
                    @endcan
                </div>
            @endforeach
        </div>
    </div>
</section>
