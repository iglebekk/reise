@props(['title', 'description' => null])

<section class="card">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-2">{{ __($title) }}</h1>

        @if ($description)
            <p class="text-body-secondary mb-4">{{ __($description) }}</p>
        @endif

        <x-app.flash-status />

        {{ $slot }}
    </div>
</section>
