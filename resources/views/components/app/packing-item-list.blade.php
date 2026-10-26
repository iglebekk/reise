@props(['trip', 'items', 'title', 'participantId' => null])

<section class="card h-100" aria-labelledby="packing-list-{{ $participantId ?? 'shared' }}">
    <div class="card-body">
        <h3 id="packing-list-{{ $participantId ?? 'shared' }}" class="h5 mb-3">{{ $title }}</h3>

        @if ($items->isEmpty())
            <p class="text-body-secondary">{{ __('trips.no_items') }}</p>
        @else
            <div class="d-grid gap-3 mb-4">
                @foreach ($items as $item)
                    <article class="border rounded p-3">
                        <x-form.form :action="route('trips.packing-items.update', [$trip, $item])" method="PATCH">
                            <input type="hidden" name="participant_id" value="{{ $participantId }}">
                            <x-form.field name="name" label="trips.item_name" :value="$item->name" :id="'packing_name_'.$item->id" required />
                            <x-form.field name="quantity" label="trips.item_quantity" type="number" min="1" :value="$item->quantity" :id="'packing_quantity_'.$item->id" required />
                            <x-form.textarea name="comment" label="trips.item_comment" rows="2" :id="'packing_comment_'.$item->id" :value="$item->comment" />
                            @if ($errors->has('participant_id'))
                                <x-app.alert variant="danger">{{ $errors->first('participant_id') }}</x-app.alert>
                            @endif
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button class="btn btn-primary btn-sm" type="submit">{{ __('trips.save_item') }}</button>
                            </div>
                        </x-form.form>
                        <x-form.form :action="route('trips.packing-items.destroy', [$trip, $item])" method="DELETE" class="mt-2">
                            <button class="btn btn-outline-danger btn-sm" type="submit">{{ __('trips.delete_item') }}</button>
                        </x-form.form>
                    </article>
                @endforeach
            </div>
        @endif

        <x-form.form :action="route('trips.packing-items.store', $trip)">
            <input type="hidden" name="participant_id" value="{{ $participantId }}">
            <h4 class="h6">{{ __('trips.add_item') }}</h4>
            <x-form.field name="name" label="trips.item_name" required />
            <x-form.field name="quantity" label="trips.item_quantity" type="number" min="1" value="1" required />
            <x-form.textarea name="comment" label="trips.item_comment" rows="2" />
            @if ($errors->has('participant_id'))
                <x-app.alert variant="danger">{{ $errors->first('participant_id') }}</x-app.alert>
            @endif
            <button class="btn btn-outline-primary" type="submit">{{ __('trips.add_item') }}</button>
        </x-form.form>
    </div>
</section>
