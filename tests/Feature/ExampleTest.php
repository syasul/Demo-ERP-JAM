<?php

declare(strict_types=1);

it('redirects root to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
