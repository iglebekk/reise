@props(['variant' => 'success'])

<div class="alert alert-{{ $variant }} mb-4" role="status" {{ $attributes }}>
    {{ $slot }}
</div>
