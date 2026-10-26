@props(['action', 'method' => 'POST'])

<form action="{{ $action }}" method="POST" {{ $attributes }}>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
