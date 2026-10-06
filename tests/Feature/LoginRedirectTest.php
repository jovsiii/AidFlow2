<?php

use App\Services\FirebaseService;
use Mockery\MockInterface;

it('redirects an admin to the dashboard after login', function () {
    $this->mock(FirebaseService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getAccount')
            ->once()
            ->with('admin')
            ->andReturn([
                'username' => 'admin',
                'password' => 'secret',
                'role' => 'admin',
            ]);
    });

    $this->post(route('login.post'), [
        'username' => 'admin',
        'password' => 'secret',
    ])->assertRedirect('/features/dashboard');
});

it('redirects a user to phone features after login', function () {
    $this->mock(FirebaseService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getAccount')
            ->once()
            ->with('user')
            ->andReturn([
                'username' => 'user',
                'password' => 'secret',
                'role' => 'user',
            ]);
    });

    $this->post(route('login.post'), [
        'username' => 'user',
        'password' => 'secret',
    ])->assertRedirect(route('phoneFeatures'));
});

it('redirects an already logged in account according to its role', function (string $role, string $route) {
    $this->withSession([
        'user' => ['role' => $role],
    ])->get(route('login'))->assertRedirect($route);
})->with([
    'admin' => ['admin', '/features/dashboard'],
    'user' => ['user', '/phoneFeatures'],
]);