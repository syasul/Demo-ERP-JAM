<?php

declare(strict_types=1);

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using email', function () {
    $user = User::factory()->create([
        'email' => 'user@erp.test',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});

test('users can authenticate using username', function () {
    $user = User::factory()->create([
        'username' => 'budi_erp',
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'budi_erp',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('inactive users cannot authenticate and receive clear message', function () {
    $inactiveUser = User::factory()->create([
        'email' => 'inactive@erp.test',
        'is_active' => false,
    ]);

    $response = $this->post('/login', [
        'email' => $inactiveUser->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.']);
});

test('login is throttled after 5 failed attempts', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
