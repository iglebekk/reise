<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('authenticates a user through the login endpoint', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertRedirect('/trips');
    $this->assertAuthenticatedAs($user);
});

it('throttles repeated login requests through the login endpoint', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertTooManyRequests();

    $this->assertGuest();
});
