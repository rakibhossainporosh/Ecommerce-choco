<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(LazilyRefreshDatabase::class);

/**
 * Task 2D, RP-2 & RP-3B — Authentication, Filament panel access, and Admin provisioning tests.
 *
 * Real panel authorization is enforced via FilamentUser and canAccessPanel().
 * Admin role provisioning is handled during make:filament-user for the admin panel.
 */
beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);
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

test('normal user created without explicit panel access has can_access_admin_panel set to false and no Admin role', function () {
    $user = User::factory()->create();

    expect($user->can_access_admin_panel)->toBeFalse()
        ->and($user->hasRole('Admin'))->toBeFalse();
});

test('initial admin provisioning flow creates user with can_access_admin_panel set to true and Admin role', function () {
    $email = 'initial-admin-'.uniqid().'@example.com';

    $this->artisan('make:filament-user', [
        '--name' => 'First Admin',
        '--email' => $email,
        '--password' => 'password123',
        '--panel' => 'admin',
    ])->assertSuccessful();

    $admin = User::where('email', $email)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->can_access_admin_panel)->toBeTrue()
        ->and($admin->hasRole('Admin'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('products.delete'))->toBeTrue()
        ->and($admin->can('orders.refund'))->toBeTrue();
});

test('make:filament-user fails cleanly with actionable error when Admin role does not exist', function () {
    // Temporarily remove Admin role to test unseeded database safety
    Role::where('name', 'Admin')->delete();

    $email = 'missing-role-'.uniqid().'@example.com';

    $this->artisan('make:filament-user', [
        '--name' => 'Admin User',
        '--email' => $email,
        '--password' => 'password123',
        '--panel' => 'admin',
    ])
        ->expectsOutputToContain('The "Admin" role does not exist. Please run: php artisan db:seed --class=RolePermissionSeeder')
        ->assertFailed();

    expect(User::where('email', $email)->exists())->toBeFalse();
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
