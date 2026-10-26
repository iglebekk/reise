<x-app.layout :title="__('auth.create_account')">
    <x-app.auth-card title="auth.create_account" description="auth.create_account_description">
        <x-form.form :action="route('register.store')">
            <x-form.field name="name" label="auth.name" autocomplete="name" required autofocus />
            <x-form.field name="email" label="auth.email_address" type="email" autocomplete="email" required />
            <x-form.field name="password" label="auth.password_label" type="password" autocomplete="new-password" required />
            <x-form.field name="password_confirmation" label="auth.confirm_password" type="password" autocomplete="new-password" required />

            <x-app.button class="w-100">{{ __('auth.create_account') }}</x-app.button>
        </x-form.form>

        <x-app.text class="mt-4 mb-0 text-center">
            {{ __('auth.have_account') }}
            <x-app.link :href="route('login')">{{ __('auth.sign_in') }}</x-app.link>
        </x-app.text>
    </x-app.auth-card>
</x-app.layout>
