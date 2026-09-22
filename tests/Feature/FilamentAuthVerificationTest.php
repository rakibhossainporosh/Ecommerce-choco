<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/**
 * Task 2D & RP-2 — Authentication and Filament panel access verification tests.
 *
 * Real panel authorization is enforced via FilamentUser and canAccessPanel().
 */
beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
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

test('normal user created without explicit panel access has can_access_admin_panel set to false', function () {
    $user = User::factory()->create();

    expect($user->can_access_admin_panel)->toBeFalse();
});

test('initial admin provisioning flow creates user with can_access_admin_panel set to true', function () {
    $email = 'initial-admin-'.uniqid().'@example.com';

    $this->artisan('make:filament-user', [
        '--name' => 'First Admin',
        '--email' => $email,
        '--password' => 'password123',
        '--panel' => 'admin',
    ])->assertSuccessful();

    $admin = User::where('email', $email)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->can_access_admin_panel)->toBeTrue();
});

test('user without panel access cannot access /admin', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => false,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('user without panel access cannot login via login form', function () {
    $user = User::factory()->create([
        'password' => 'correct-password',
        'can_access_admin_panel' => false,
    ]);

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertGuest();
});

test('user with panel access can authenticate and access /admin', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('authenticated user with panel access sees the dashboard', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

test('panel access is denied for panels other than admin', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    $otherPanel = (new Panel)->id('other');

    expect($user->canAccessPanel($otherPanel))->toBeFalse();
});

test('no role or permission is required for panel access', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    expect($user->roles)->toBeEmpty()
        ->and($user->permissions)->toBeEmpty()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('user can logout from Filament', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    $this->actingAs($user)
        ->post('/admin/logout');

    $this->assertGuest();
});

test('after logout guest is redirected to login', function () {
    $user = User::factory()->create([
        'can_access_admin_panel' => true,
    ]);

    $this->actingAs($user)
        ->post('/admin/logout');

    $this->assertGuest();

    $this->get('/admin')
        ->assertRedirect('/admin/login');
});
