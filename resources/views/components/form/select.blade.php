@props(['name', 'label', 'options', 'category', 'required' => false, 'value' => null])

<div class="form-field mb-3">
    <label for="{{ $name }}" class="form-label">{{ __($label) }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}
        @required($required)
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        aria-describedby="{{ $name }}-error"
    >
        <option value="">{{ __('trips.choose_option') }}</option>
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected((string) old($name, $value) === (string) $option)>{{ __('trips.'.$category.'.'.$option) }}</option>
        @endforeach
    </select>
    <div id="{{ $name }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
</div>
