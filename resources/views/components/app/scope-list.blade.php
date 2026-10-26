@props(['scopes'])

<ul class="list-group mb-4">
    @forelse ($scopes as $scope)
        <li class="list-group-item">
            <strong>{{ $scope->id }}</strong>
            <span class="d-block text-body-secondary">{{ $scope->description }}</span>
        </li>
    @empty
        <li class="list-group-item text-body-secondary">{{ __('mcp.no_scopes') }}</li>
    @endforelse
</ul>
