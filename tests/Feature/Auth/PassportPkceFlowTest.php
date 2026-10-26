<?php

use App\Actions\RemoveTripMember;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

it('advertises the protected MCP resource scope', function () {
    $this->getJson('/.well-known/oauth-protected-resource')
        ->assertOk()
        ->assertJsonPath('scopes_supported.0', 'mcp:use');

    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('scopes_supported.0', 'mcp:use');
});

it('exchanges a public PKCE authorization code and uses its bearer token for MCP', function () {
    foreach (['oauth_clients', 'oauth_auth_codes', 'oauth_access_tokens', 'oauth_refresh_tokens'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    $user = User::factory()->create();
    $admin = User::factory()->create();
    $trip = Trip::factory()->create();
    $trip->members()->attach($admin, ['role' => 'admin']);
    $trip->members()->attach($user, ['role' => 'member']);
    $redirectUri = 'https://client.example/callback';
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Trip MCP Client',
        [$redirectUri],
        confidential: false,
    );
    $codeVerifier = str_repeat('A', 64);
    $codeChallenge = rtrim(strtr(
        base64_encode(hash('sha256', $codeVerifier, true)),
        '+/',
        '-_',
    ), '=');
    $state = 'pkce-state-value';

    $authorization = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'consent',
    ]));

    $authorization->assertOk()
        ->assertSee('Trip MCP Client')
        ->assertSee($redirectUri)
        ->assertSee('Use MCP server')
        ->assertViewHas('authToken');

    $approval = $this->post(route('passport.authorizations.approve'), [
        'client_id' => $client->getKey(),
        'state' => $state,
        'auth_token' => $authorization->viewData('authToken'),
    ]);
    $approval->assertRedirect();

    parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $callbackQuery);

    expect($callbackQuery['state'] ?? null)->toBe($state)
        ->and($callbackQuery['code'] ?? null)->toBeString()
        ->and($callbackQuery['code'] ?? '')->not->toBeEmpty();

    $tokenResponse = $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'code' => $callbackQuery['code'],
        'code_verifier' => $codeVerifier,
    ], ['Accept' => 'application/json']);

    $tokenResponse->assertOk()
        ->assertJsonStructure(['token_type', 'expires_in', 'access_token', 'refresh_token']);

    $storedScopes = DB::table('oauth_access_tokens')
        ->where('user_id', $user->getKey())
        ->where('client_id', $client->getKey())
        ->value('scopes');

    expect(json_decode((string) $storedScopes, true))->toContain('mcp:use');

    $accessToken = $tokenResponse->json('access_token');

    $this->withToken($accessToken)
        ->getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonFragment(['id' => $trip->getKey()]);

    $this->withToken($accessToken)
        ->getJson('/api/v1/trips/'.$trip->getKey())
        ->assertOk()
        ->assertJsonPath('data.id', $trip->getKey());

    $this->withToken($accessToken)
        ->postJson('/mcp/trips', [
            'jsonrpc' => '2.0',
            'id' => 'pkce-show-before-removal',
            'method' => 'tools/call',
            'params' => [
                'name' => 'show_trip',
                'arguments' => ['trip_id' => $trip->getKey()],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.isError', false);

    $admin->setCurrentTenant($trip);
    (new RemoveTripMember)($admin, $trip, $user);

    $this->withToken($accessToken)
        ->getJson('/api/v1/trips')
        ->assertOk()
        ->assertJsonMissing(['id' => $trip->getKey()]);

    $this->withToken($accessToken)
        ->getJson('/api/v1/trips/'.$trip->getKey())
        ->assertNotFound();

    $this->withToken($accessToken)
        ->postJson('/mcp/trips', [
            'jsonrpc' => '2.0',
            'id' => 'pkce-show-after-removal',
            'method' => 'tools/call',
            'params' => [
                'name' => 'show_trip',
                'arguments' => ['trip_id' => $trip->getKey()],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonFragment(['text' => 'Trip not found.']);

    $this->withToken($accessToken)
        ->postJson('/mcp/trips', [
            'jsonrpc' => '2.0',
            'id' => 'pkce-initialize',
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'PKCE feature test',
                    'version' => '1.0.0',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('jsonrpc', '2.0')
        ->assertJsonPath('id', 'pkce-initialize');
});

it('rejects an authorization code exchanged with the wrong PKCE verifier', function () {
    $user = User::factory()->create();
    $redirectUri = 'https://client.example/callback';
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Trip MCP Client',
        [$redirectUri],
        confidential: false,
    );
    $codeVerifier = str_repeat('B', 64);
    $codeChallenge = rtrim(strtr(
        base64_encode(hash('sha256', $codeVerifier, true)),
        '+/',
        '-_',
    ), '=');
    $state = 'wrong-verifier-state';

    $authorization = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'consent',
    ]));
    $authorization->assertOk();

    $approval = $this->post(route('passport.authorizations.approve'), [
        'client_id' => $client->getKey(),
        'state' => $state,
        'auth_token' => $authorization->viewData('authToken'),
    ]);
    $approval->assertRedirect();

    parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $callbackQuery);

    $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => $redirectUri,
        'code' => $callbackQuery['code'],
        'code_verifier' => str_repeat('C', 64),
    ], ['Accept' => 'application/json'])
        ->assertBadRequest()
        ->assertJsonPath('error', 'invalid_grant');
});

it('rejects HTTP MCP initialization without Passport authentication', function () {
    $this->postJson('/mcp/trips', [
        'jsonrpc' => '2.0',
        'id' => 'unauthenticated-initialize',
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => [],
            'clientInfo' => [
                'name' => 'Unauthenticated feature test',
                'version' => '1.0.0',
            ],
        ],
    ])->assertUnauthorized();
});
