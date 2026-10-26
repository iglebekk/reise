<?php

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;

it('shows the OAuth client, redirect address, scopes, and decision forms', function () {
    $client = new Client([
        'name' => 'Trip Planner',
        'redirect_uris' => ['https://client.example/callback'],
    ]);
    $request = Request::create('/oauth/authorize?state=oauth-state', 'GET');

    $html = view('mcp.authorize', [
        'authToken' => 'authorization-token',
        'client' => $client,
        'request' => $request,
        'scopes' => [new Scope('trips:read', 'Read your trips')],
        'user' => new User(['email' => 'traveler@example.test']),
    ])->render();

    expect($html)
        ->toContain('Trip Planner')
        ->toContain('https://client.example/callback')
        ->toContain('trips:read')
        ->toContain('value="oauth-state"')
        ->toContain('value="authorization-token"')
        ->toContain('name="_method" value="DELETE"')
        ->toContain(route('passport.authorizations.approve'));
});
