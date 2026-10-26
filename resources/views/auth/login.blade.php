<x-app.layout :title="__('auth.sign_in')">
    <x-app.auth-card title="auth.sign_in" description="auth.sign_in_description">
        <x-form.form :action="route('login.store')">
            <x-form.field name="email" label="auth.email_address" type="email" autocomplete="username" required autofocus />
            <x-form.field name="password" label="auth.password_label" type="password" autocomplete="current-password" required />

            <x-app.button class="w-100">{{ __('auth.sign_in') }}</x-app.button>
        </x-form.form>

        <x-app.row class="d-flex justify-content-between mt-4">
            <x-app.link :href="route('password.request')">{{ __('auth.forgot_password') }}</x-app.link>
            <x-app.link :href="route('register')">{{ __('auth.create_account') }}</x-app.link>
        </x-app.row>
    </x-app.auth-card>
</x-app.layout>
