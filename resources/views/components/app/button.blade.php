@props(['type' => 'submit', 'appearance' => 'btn btn-primary'])

<button type="{{ $type }}" {{ $attributes->class([$appearance]) }}>
    {{ $slot }}
</button>
