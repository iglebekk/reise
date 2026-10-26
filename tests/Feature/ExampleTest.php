<?php

test('the root redirects guests through the trips page to sign in', function () {
    $this->get('/')
        ->assertRedirect(route('trips.index'));

    $this->get(route('trips.index'))
        ->assertRedirect(route('login'));
});
