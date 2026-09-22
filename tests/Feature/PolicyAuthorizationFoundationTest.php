<?php

use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    $this->categoryPolicy = new CategoryPolicy;
    $this->productPolicy = new ProductPolicy;
    $this->orderPolicy = new OrderPolicy;
});

test('A: Admin can authorize all representative policy abilities via centralized Gate::before', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    // CategoryPolicy
    expect($this->categoryPolicy->viewAny($admin))->toBeTrue()
        ->and($this->categoryPolicy->view($admin))->toBeTrue()
        ->and($this->categoryPolicy->create($admin))->toBeTrue()
        ->and($this->categoryPolicy->update($admin))->toBeTrue()
        ->and($this->categoryPolicy->delete($admin))->toBeTrue()
        ->and($this->categoryPolicy->deleteAny($admin))->toBeTrue();

    // ProductPolicy
    expect($this->productPolicy->viewAny($admin))->toBeTrue()
        ->and($this->productPolicy->view($admin))->toBeTrue()
        ->and($this->productPolicy->create($admin))->toBeTrue()
        ->and($this->productPolicy->update($admin))->toBeTrue()
        ->and($this->productPolicy->delete($admin))->toBeTrue()
        ->and($this->productPolicy->deleteAny($admin))->toBeTrue();

    // OrderPolicy
    expect($this->orderPolicy->viewAny($admin))->toBeTrue()
        ->and($this->orderPolicy->view($admin))->toBeTrue()
        ->and($this->orderPolicy->create($admin))->toBeTrue()
        ->and($this->orderPolicy->update($admin))->toBeTrue()
        ->and($this->orderPolicy->cancel($admin))->toBeTrue()
        ->and($this->orderPolicy->refund($admin))->toBeTrue();
});

test('B: Manager CategoryPolicy authorization (viewAny/create/update allowed, delete denied)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->categoryPolicy->viewAny($manager))->toBeTrue()
        ->and($this->categoryPolicy->view($manager))->toBeTrue()
        ->and($this->categoryPolicy->create($manager))->toBeTrue()
        ->and($this->categoryPolicy->update($manager))->toBeTrue()
        ->and($this->categoryPolicy->delete($manager))->toBeFalse()
        ->and($this->categoryPolicy->deleteAny($manager))->toBeFalse();
});

test('C: Manager ProductPolicy authorization (viewAny/create/update allowed, delete denied)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->productPolicy->viewAny($manager))->toBeTrue()
        ->and($this->productPolicy->view($manager))->toBeTrue()
        ->and($this->productPolicy->create($manager))->toBeTrue()
        ->and($this->productPolicy->update($manager))->toBeTrue()
        ->and($this->productPolicy->delete($manager))->toBeFalse()
        ->and($this->productPolicy->deleteAny($manager))->toBeFalse();
});

test('D: Manager OrderPolicy authorization (viewAny/create/update/cancel allowed, refund denied)', function () {
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect($this->orderPolicy->viewAny($manager))->toBeTrue()
        ->and($this->orderPolicy->view($manager))->toBeTrue()
        ->and($this->orderPolicy->create($manager))->toBeTrue()
        ->and($this->orderPolicy->update($manager))->toBeTrue()
        ->and($this->orderPolicy->cancel($manager))->toBeTrue()
        ->and($this->orderPolicy->refund($manager))->toBeFalse();
});

test('E: Staff ProductPolicy authorization (viewAny allowed, create/update/delete denied)', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($this->productPolicy->viewAny($staff))->toBeTrue()
        ->and($this->productPolicy->view($staff))->toBeTrue()
        ->and($this->productPolicy->create($staff))->toBeFalse()
        ->and($this->productPolicy->update($staff))->toBeFalse()
        ->and($this->productPolicy->delete($staff))->toBeFalse()
        ->and($this->productPolicy->deleteAny($staff))->toBeFalse();
});

test('F: Staff OrderPolicy authorization (viewAny/create/update allowed, cancel/refund denied)', function () {
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect($this->orderPolicy->viewAny($staff))->toBeTrue()
        ->and($this->orderPolicy->view($staff))->toBeTrue()
        ->and($this->orderPolicy->create($staff))->toBeTrue()
        ->and($this->orderPolicy->update($staff))->toBeTrue()
        ->and($this->orderPolicy->cancel($staff))->toBeFalse()
        ->and($this->orderPolicy->refund($staff))->toBeFalse();
});

test('G: A user with no role and no permission is denied across all policy methods', function () {
    $guestUser = User::factory()->create();

    // CategoryPolicy
    expect($this->categoryPolicy->viewAny($guestUser))->toBeFalse()
        ->and($this->categoryPolicy->view($guestUser))->toBeFalse()
        ->and($this->categoryPolicy->create($guestUser))->toBeFalse()
        ->and($this->categoryPolicy->update($guestUser))->toBeFalse()
        ->and($this->categoryPolicy->delete($guestUser))->toBeFalse()
        ->and($this->categoryPolicy->deleteAny($guestUser))->toBeFalse();

    // ProductPolicy
    expect($this->productPolicy->viewAny($guestUser))->toBeFalse()
        ->and($this->productPolicy->view($guestUser))->toBeFalse()
        ->and($this->productPolicy->create($guestUser))->toBeFalse()
        ->and($this->productPolicy->update($guestUser))->toBeFalse()
        ->and($this->productPolicy->delete($guestUser))->toBeFalse()
        ->and($this->productPolicy->deleteAny($guestUser))->toBeFalse();

    // OrderPolicy
    expect($this->orderPolicy->viewAny($guestUser))->toBeFalse()
        ->and($this->orderPolicy->view($guestUser))->toBeFalse()
        ->and($this->orderPolicy->create($guestUser))->toBeFalse()
        ->and($this->orderPolicy->update($guestUser))->toBeFalse()
        ->and($this->orderPolicy->cancel($guestUser))->toBeFalse()
        ->and($this->orderPolicy->refund($guestUser))->toBeFalse();
});

test('H: Policy checks do not depend on role names, only permissions/abilities', function () {
    $userWithoutRole = User::factory()->create();

    // Directly give a permission without giving any role
    $userWithoutRole->givePermissionTo('products.delete');

    // Policy respects the ability directly
    expect($this->productPolicy->delete($userWithoutRole))->toBeTrue()
        ->and($this->productPolicy->create($userWithoutRole))->toBeFalse();
});

test('I: Admin role does NOT bypass can_access_admin_panel', function () {
    // Admin with can_access_admin_panel = false
    $adminWithoutPanel = User::factory()->create(['can_access_admin_panel' => false]);
    $adminWithoutPanel->assignRole('Admin');

    // Policy authorization succeeds
    expect($this->productPolicy->delete($adminWithoutPanel))->toBeTrue()
        ->and($this->orderPolicy->refund($adminWithoutPanel))->toBeTrue();

    // Panel access is strictly denied (HTTP 403)
    $this->actingAs($adminWithoutPanel)
        ->get('/admin')
        ->assertForbidden();

    // Admin with can_access_admin_panel = true
    $adminWithPanel = User::factory()->create(['can_access_admin_panel' => true]);
    $adminWithPanel->assignRole('Admin');

    // Panel access is granted (HTTP 200)
    $this->actingAs($adminWithPanel)
        ->get('/admin')
        ->assertOk();
});

/**
 * NOTE ON POLICY AUTO-DISCOVERY:
 * Laravel 13 discovers policies matching App\Models\{Model} -> App\Policies\{Model}Policy
 * automatically. Because the Category, Product, and Order Eloquent models do not exist yet,
 * framework auto-discovery for those specific models is NOT active or verified today.
 *
 * This test verifies that Laravel's Gate dispatcher successfully routes ability checks
 * through to these policies via the Gate pipeline (using temporary test stubs with Gate::policy).
 * Real model policy auto-discovery will be verified once the business models are created.
 */
test('Gate pipeline integration: policies resolve abilities via Gate using temporary test stubs', function () {
    $categoryStub = new class {};
    $productStub = new class {};
    $orderStub = new class {};

    Gate::policy(get_class($categoryStub), CategoryPolicy::class);
    Gate::policy(get_class($productStub), ProductPolicy::class);
    Gate::policy(get_class($orderStub), OrderPolicy::class);

    // 1. Admin bypasses all restrictions through Gate::before in the Gate pipeline
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    expect(Gate::forUser($admin)->allows('delete', $categoryStub))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $productStub))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('refund', $orderStub))->toBeTrue();

    // 2. Manager restrictions through Gate
    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    expect(Gate::forUser($manager)->allows('create', $categoryStub))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $categoryStub))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('update', $productStub))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $productStub))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('cancel', $orderStub))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('refund', $orderStub))->toBeFalse();

    // 3. Staff restrictions through Gate
    $staff = User::factory()->create();
    $staff->assignRole('Staff');

    expect(Gate::forUser($staff)->allows('view', $productStub))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('delete', $productStub))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('update', $orderStub))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('cancel', $orderStub))->toBeFalse();

    // 4. Guest / user with no permissions through Gate
    $guest = User::factory()->create();

    expect(Gate::forUser($guest)->allows('view', $categoryStub))->toBeFalse()
        ->and(Gate::forUser($guest)->allows('view', $productStub))->toBeFalse()
        ->and(Gate::forUser($guest)->allows('view', $orderStub))->toBeFalse();
});
