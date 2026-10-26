<x-app.layout :title="__('auth.reset_password')">
    <x-app.auth-card title="auth.reset_password" description="auth.reset_password_description">
        <x-form.form :action="route('password.update')">
            <x-form.hidden name="token" :value="$token" />
            <x-form.field name="email" label="auth.email_address" type="email" autocomplete="email" :value="$email" required autofocus />
            <x-form.field name="password" label="auth.password_label" type="password" autocomplete="new-password" required />
            <x-form.field name="password_confirmation" label="auth.confirm_password" type="password" autocomplete="new-password" required />

            <x-app.button class="w-100">{{ __('auth.reset_password') }}</x-app.button>
        </x-form.form>
    </x-app.auth-card>
</x-app.layout>
