@props(['trip', 'tripTypes', 'transportOptions', 'accommodationOptions', 'activityOptions'])

<x-app.page-header :title="__('trips.edit_trip')" :description="__('trips.edit_description')" />

@if ($errors->any())
    <x-app.alert variant="danger" role="alert">{{ __('trips.form_error') }}</x-app.alert>
@endif

<section class="card">
    <div class="card-body p-3 p-md-4">
        <x-form.form :action="route('trips.update', $trip)" method="PATCH">
            <input type="hidden" name="metadata_form" value="1">
            <x-form.field name="destination" label="trips.destination" :value="$trip->destination" required />
            <div class="row">
                <div class="col-12 col-sm-6">
                    <x-form.field name="start_date" label="trips.start_date" type="date" :value="$trip->start_date->toDateString()" required />
                </div>
                <div class="col-12 col-sm-6">
                    <x-form.field name="end_date" label="trips.end_date" type="date" :value="$trip->end_date->toDateString()" required />
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-4">
                    <x-form.select name="type" label="trips.type" :options="$tripTypes" category="types" :value="$trip->type" required />
                </div>
                <div class="col-12 col-md-4">
                    <x-form.select name="transport" label="trips.transport" :options="$transportOptions" category="transport_options" :value="$trip->transport" required />
                </div>
                <div class="col-12 col-md-4">
                    <x-form.select name="accommodation" label="trips.accommodation" :options="$accommodationOptions" category="accommodation_options" :value="$trip->accommodation" required />
                </div>
            </div>
            <x-form.checkbox-group name="activities" label="trips.activities" :options="$activityOptions" :value="$trip->activities ?? []" />
            <x-form.textarea name="description" label="trips.description" :value="$trip->description" />
            <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="{{ route('trips.show', $trip) }}">{{ __('trips.cancel') }}</a>
                <button class="btn btn-primary" type="submit">{{ __('trips.save_changes') }}</button>
            </div>
        </x-form.form>
    </div>
</section>
