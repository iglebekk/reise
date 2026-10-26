<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('registers an unverified user and sends a verification email', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Trip Planner',
        'email' => 'planner@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/trips');

    $user = User::query()->where('email', 'planner@example.test')->firstOrFail();

    expect($user->email_verified_at)->toBeNull();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Trip Planner',
    ]);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects missing registration fields', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password']);

    $this->assertDatabaseCount('users', 0);
});

it('rejects a duplicate email address', function () {
    User::factory()->create(['email' => 'planner@example.test']);

    $this->post(route('register.store'), [
        'name' => 'Another Planner',
        'email' => 'planner@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])
        ->assertSessionHasErrors(['email']);

    $this->assertDatabaseCount('users', 1);
});

it('rejects a password confirmation that does not match', function () {
    $this->post(route('register.store'), [
        'name' => 'Trip Planner',
        'email' => 'planner@example.test',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
    ])
        ->assertSessionHasErrors(['password']);

    $this->assertDatabaseCount('users', 0);
});
