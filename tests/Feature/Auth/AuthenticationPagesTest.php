<?php

use App\Models\User;

it('renders the Fortify guest authentication pages with translated labels', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSeeText(__('auth.sign_in'))
        ->assertSeeText(__('auth.email_address'));

    $this->get(route('register'))
        ->assertOk()
        ->assertSeeText(__('auth.create_account'));

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSeeText(__('auth.send_reset_link'));

    $this->get(route('password.reset', ['token' => 'reset-token']))
        ->assertOk()
        ->assertSee('reset-token');
});

it('renders the email verification prompt for an authenticated user', function () {
    $user = User::factory()->unverified()->make();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSeeText(__('auth.verify_email'))
        ->assertSeeText(__('auth.resend_verification'));
});
