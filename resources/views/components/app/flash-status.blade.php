@if (session('status'))
    <x-app.alert>
        {{ __('auth.statuses.'.session('status')) }}
    </x-app.alert>
@endif
