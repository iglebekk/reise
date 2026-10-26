@props(['title'])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} · {{ __('auth.app_name') }}</title>
        <script>document.documentElement.dataset.bsTheme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-body-tertiary">
        <nav class="navbar bg-body border-bottom">
            <div class="container d-flex justify-content-between">
                <a class="navbar-brand fw-semibold" href="{{ route('trips.index') }}">{{ __('auth.app_name') }}</a>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-body-secondary d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm" type="submit">{{ __('auth.sign_out') }}</button>
                    </form>
                </div>
            </div>
        </nav>
        <main class="container py-4 py-md-5">
            {{ $slot }}
        </main>
    </body>
</html>
