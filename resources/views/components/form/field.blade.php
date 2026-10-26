@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'value' => null,
])

<div class="form-field mb-3">
    <label for="{{ $name }}" class="form-label">{{ __($label) }}</label>
    <input
        {{ $attributes->merge([
            'type' => $type,
            'name' => $name,
            'id' => $name,
            'value' => $type === 'password' ? null : old($name, $value),
            'autocomplete' => $autocomplete,
            'required' => $required,
            'autofocus' => $autofocus,
            'aria-invalid' => $errors->has($name) ? 'true' : 'false',
            'aria-describedby' => $name.'-error',
        ])->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
    >
    <div id="{{ $name }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
</div>
