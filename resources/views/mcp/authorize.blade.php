<x-app.layout :title="__('mcp.authorization_title')">
    <x-app.auth-card title="mcp.authorization_title">
        <x-app.text>{{ __('mcp.authorization_summary', ['client' => $client->name]) }}</x-app.text>
        <x-app.text class="text-body-secondary">{{ __('mcp.signed_in_as', ['email' => $user->email]) }}</x-app.text>
        <x-app.client-details :client="$client" />

        <x-app.section-heading>{{ __('mcp.requested_scopes') }}</x-app.section-heading>
        <x-app.scope-list :scopes="$scopes" />

        <x-app.row class="row g-2">
            <x-app.column class="col-12 col-sm-6">
                <x-form.form :action="route('passport.authorizations.approve')">
                    <x-form.hidden name="state" :value="$request->state" />
                    <x-form.hidden name="client_id" :value="$client->id" />
                    <x-form.hidden name="auth_token" :value="$authToken" />
                    <x-app.button class="w-100">{{ __('mcp.approve') }}</x-app.button>
                </x-form.form>
            </x-app.column>
            <x-app.column class="col-12 col-sm-6">
                <x-form.form :action="route('passport.authorizations.deny')" method="DELETE">
                    <x-form.hidden name="state" :value="$request->state" />
                    <x-form.hidden name="client_id" :value="$client->id" />
                    <x-form.hidden name="auth_token" :value="$authToken" />
                    <x-app.button appearance="btn btn-outline-secondary" class="w-100">
                        {{ __('mcp.deny') }}
                    </x-app.button>
                </x-form.form>
            </x-app.column>
        </x-app.row>
    </x-app.auth-card>
</x-app.layout>
