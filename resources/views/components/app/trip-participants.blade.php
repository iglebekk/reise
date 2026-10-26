@props(['trip', 'members'])

<section class="mb-4" aria-labelledby="participants-heading">
    <header class="mb-3">
        <h2 id="participants-heading" class="h4 mb-1">{{ __('trips.participants') }}</h2>
        <p class="text-body-secondary mb-0">{{ __('trips.participants_description') }}</p>
    </header>

    @if ($trip->participants->isEmpty())
        <p class="text-body-secondary">{{ __('trips.no_participants') }}</p>
    @else
        <div class="row g-3 mb-4">
            @foreach ($trip->participants as $participant)
                <div class="col-12 col-lg-6">
                    <section class="card h-100" aria-labelledby="participant-{{ $participant->id }}">
                        <div class="card-body">
                            <h3 id="participant-{{ $participant->id }}" class="h5">{{ $participant->name }}</h3>
                            <x-form.form :action="route('trips.participants.update', [$trip, $participant])" method="PATCH">
                                <x-form.field name="name" label="trips.participant_name" :value="$participant->name" :id="'participant_name_'.$participant->id" required />
                                <x-form.field name="age" label="trips.participant_age" type="number" min="0" max="120" :value="$participant->age" :id="'participant_age_'.$participant->id" />
                                <div class="form-field mb-3">
                                    <label for="user_id_{{ $participant->id }}" class="form-label">{{ __('trips.participant_member') }}</label>
                                    <select id="user_id_{{ $participant->id }}" name="user_id" @class(['form-select', 'is-invalid' => $errors->has('user_id')]) aria-invalid="{{ $errors->has('user_id') ? 'true' : 'false' }}" aria-describedby="user_id_error_{{ $participant->id }}">
                                        <option value="">{{ __('trips.no_account') }}</option>
                                        @foreach ($members as $member)
                                            <option value="{{ $member->id }}" @selected((string) old('user_id', $participant->user_id) === (string) $member->id)>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                    <div id="user_id_error_{{ $participant->id }}" class="invalid-feedback">{{ $errors->first('user_id') }}</div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <button class="btn btn-primary" type="submit">{{ __('trips.edit_participant') }}</button>
                                </div>
                            </x-form.form>
                            <x-form.form :action="route('trips.participants.destroy', [$trip, $participant])" method="DELETE" class="mt-2">
                                <button class="btn btn-outline-danger" type="submit">{{ __('trips.delete_participant') }}</button>
                            </x-form.form>
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    @endif

    <section class="card" aria-labelledby="add-participant-heading">
        <div class="card-body">
            <h3 id="add-participant-heading" class="h5">{{ __('trips.add_participant') }}</h3>
            <x-form.form :action="route('trips.participants.store', $trip)">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <x-form.field name="name" label="trips.participant_name" required />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-form.field name="age" label="trips.participant_age" type="number" min="0" max="120" />
                    </div>
                </div>
                <div class="form-field mb-3">
                    <label for="new_participant_user_id" class="form-label">{{ __('trips.participant_member') }}</label>
                    <select id="new_participant_user_id" name="user_id" @class(['form-select', 'is-invalid' => $errors->has('user_id')]) aria-invalid="{{ $errors->has('user_id') ? 'true' : 'false' }}" aria-describedby="new_participant_user_id_error">
                        <option value="">{{ __('trips.no_account') }}</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected((string) old('user_id') === (string) $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <div id="new_participant_user_id_error" class="invalid-feedback">{{ $errors->first('user_id') }}</div>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('trips.add_participant') }}</button>
            </x-form.form>
        </div>
    </section>
</section>
