@props(['trip'])

<section class="mb-4" aria-labelledby="packing-lists-heading">
    <h2 id="packing-lists-heading" class="h4 mb-3">{{ __('trips.packing_lists') }}</h2>
    <div class="row g-3">
        <div class="col-12">
            <x-app.packing-item-list
                :trip="$trip"
                :items="$trip->packingItems"
                :title="__('trips.shared_items')"
            />
        </div>
        @foreach ($trip->participants as $participant)
            <div class="col-12 col-lg-6">
                <x-app.packing-item-list
                    :trip="$trip"
                    :items="$participant->packingItems"
                    :participant-id="$participant->id"
                    :title="__('trips.participant_items', ['name' => $participant->name])"
                />
            </div>
        @endforeach
    </div>
</section>
