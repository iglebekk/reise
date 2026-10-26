@props(['client'])

<dl class="row mb-4">
    <dt class="col-sm-4">{{ __('mcp.client_name') }}</dt>
    <dd class="col-sm-8">{{ $client->name }}</dd>

    <dt class="col-sm-4">{{ __('mcp.redirect_uri') }}</dt>
    <dd class="col-sm-8">
        <ul class="list-unstyled mb-0">
            @foreach ($client->redirect_uris ?? [] as $redirectUri)
                <li>{{ $redirectUri }}</li>
            @endforeach
        </ul>
    </dd>
</dl>
