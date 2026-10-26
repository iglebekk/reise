<x-app.layout :title="__('auth.forgot_password')">
    <x-app.auth-card title="auth.forgot_password" description="auth.forgot_password_description">
        <x-form.form :action="route('password.email')">
            <x-form.field name="email" label="auth.email_address" type="email" autocomplete="email" required autofocus />
            <x-app.button class="w-100">{{ __('auth.send_reset_link') }}</x-app.button>
        </x-form.form>

        <x-app.text class="mt-4 mb-0 text-center">
            <x-app.link :href="route('login')">{{ __('auth.back_to_sign_in') }}</x-app.link>
        </x-app.text>
    </x-app.auth-card>
</x-app.layout>
