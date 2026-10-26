@props(['name', 'label', 'options', 'value' => []])

<fieldset class="form-field mb-3">
    <legend class="form-label">{{ __($label) }}</legend>
    <div class="row g-2">
        @foreach ($options as $option)
            <div class="col-12 col-sm-6">
                <label class="form-check d-flex align-items-center gap-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="{{ $name }}[]"
                        value="{{ $option }}"
                        @checked(in_array($option, old($name, $value), true))
                    >
                    <span class="form-check-label">{{ __('trips.activity_options.'.$option) }}</span>
                </label>
            </div>
        @endforeach
    </div>
    @if ($errors->has($name) || $errors->has($name.'.*'))
        <div class="invalid-feedback d-block">{{ $errors->first($name) ?: $errors->first($name.'.*') }}</div>
    @endif
</fieldset>
