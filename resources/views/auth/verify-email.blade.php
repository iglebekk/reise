<x-app.layout :title="__('auth.verify_email')">
    <x-app.auth-card title="auth.verify_email" description="auth.verify_email_description">
        <x-form.form :action="route('verification.send')">
            <x-app.button class="w-100">{{ __('auth.resend_verification') }}</x-app.button>
        </x-form.form>

        <x-form.form :action="route('logout')" class="mt-3">
            <x-app.button class="w-100">{{ __('auth.sign_out') }}</x-app.button>
        </x-form.form>
    </x-app.auth-card>
</x-app.layout>
