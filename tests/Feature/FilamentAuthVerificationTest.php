<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * Task 2D — Authentication verification tests.
 *
 * These tests verify the Filament authentication flow for the admin panel.
 * The app environment is set to 'local' to match the current deployment,
 * since the User model does not yet implement FilamentUser (planned for
 * the Roles & Permissions phase).
 */
beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    $this->app['config']->set('app.env', 'local');
});

test('guest accessing /admin is redirected to login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

test('login page loads successfully', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee('Sign in');
});

test('invalid credentials are rejected', function () {
    $response = $this
        ->post('/admin/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

    $this->assertGuest();
});

test('valid user can authenticate and access admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('authenticated user sees the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

test('user can logout from Filament', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/admin/logout');

    $this->assertGuest();
});

test('after logout guest is redirected to login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/admin/logout');

    $this->assertGuest();

    $this->get('/admin')
        ->assertRedirect('/admin/login');
});
