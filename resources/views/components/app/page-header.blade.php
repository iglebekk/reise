@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null])

<header class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3 mb-4">
    <div>
        <h1 class="h2 mb-1">{{ $title }}</h1>
        @if ($description)
            <p class="text-body-secondary mb-0">{{ $description }}</p>
        @endif
    </div>
    @if ($action && $actionLabel)
        <a class="btn btn-primary flex-shrink-0" href="{{ $action }}">{{ $actionLabel }}</a>
    @endif
</header>
