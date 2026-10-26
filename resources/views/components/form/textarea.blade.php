@props(['name', 'label', 'rows' => 4, 'value' => null, 'id' => null])

<div class="form-field mb-3">
    <label for="{{ $id ?? $name }}" class="form-label">{{ __($label) }}</label>
    <textarea
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        aria-describedby="{{ $name }}-error"
    >{{ old($name, $value) }}</textarea>
    <div id="{{ $name }}-error" class="invalid-feedback">{{ $errors->first($name) }}</div>
</div>
