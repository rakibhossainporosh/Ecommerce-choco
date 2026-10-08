<?php

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    $this->seed(RolePermissionSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('customer_addresses')->truncate();
    DB::table('orders')->truncate();
    DB::table('customers')->truncate();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('1. CustomerResource binds to Customer model', function () {
    expect(CustomerResource::getModel())->toBe(Customer::class);
});

test('2. Admin can access CustomerResource index, edit, and view pages', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $this->actingAs($admin);
    expect(CustomerResource::canAccess())->toBeTrue()
        ->and(CustomerResource::canCreate())->toBeTrue();

    $customer = Customer::factory()->create();

    $this->actingAs($admin)
        ->get(CustomerResource::getUrl('view', ['record' => $customer]))
        ->assertOk();

    $this->actingAs($admin)
        ->get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertOk();
});

test('3. Manager can access CustomerResource pages', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $this->actingAs($manager);
    expect(CustomerResource::canAccess())->toBeTrue()
        ->and(CustomerResource::canCreate())->toBeTrue();

    $customer = Customer::factory()->create();

    $this->actingAs($manager)
        ->get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertOk();
});

test('4. Staff can access CustomerResource and has create capability', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');

    $this->actingAs($staff);
    expect(CustomerResource::canAccess())->toBeTrue()
        ->and(CustomerResource::canCreate())->toBeTrue();
});

test('5. Unauthenticated guest is redirected to admin login', function () {
    $customer = Customer::factory()->create();

    $this->get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertRedirect('/admin/login');
});

test('6. User with can_access_admin_panel set to false is forbidden', function () {
    $user = User::factory()->create(['can_access_admin_panel' => false]);
    $user->assignRole('Admin');

    $customer = Customer::factory()->create();

    $this->actingAs($user)
        ->get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertForbidden();
});

test('7. Customer creation is configured as a modal on ListCustomers page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    expect(CustomerResource::getPages())->not->toHaveKey('create')
        ->and(CustomerResource::canCreate())->toBeTrue();

    $listPage = new ListCustomers;
    $actions = invade($listPage)->getHeaderActions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0]->getName())->toBe('create')
        ->and($actions[0]->getModalHeading())->toBe('Create Customer');
});

test('8. Duplicate phone number is rejected during customer form validation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Customer::factory()->create(['phone' => '01899887766']);
    $customerToEdit = Customer::factory()->create(['phone' => '01711002233']);

    Livewire::test(EditCustomer::class, ['record' => $customerToEdit->getRouteKey()])
        ->fillForm([
            'phone' => '01899887766',
        ])
        ->call('save')
        ->assertHasFormErrors(['phone' => 'unique']);
});

test('9. Admin can edit customer details via EditCustomer page', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $customer = Customer::factory()->create([
        'name' => 'Old Customer Name',
        'phone' => '01911002233',
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Customer Name',
            'phone' => '01911002233',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->fresh()->name)->toBe('Updated Customer Name')
        ->and($customer->fresh()->is_active)->toBeFalse();
});

test('10. Admin can delete a customer with zero orders', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('delete');

    expect(Customer::find($customer->id))->toBeNull();
});

test('11. Admin cannot delete a customer who has placed orders', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $customer = Customer::factory()->create();
    Order::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('delete')
        ->assertNotified('Cannot delete customer with placed orders.');

    expect(Customer::find($customer->id))->not->toBeNull();
});

test('12. Manager cannot delete customer', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $customer = Customer::factory()->create();

    expect(CustomerResource::canDelete($customer))->toBeFalse();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertActionHidden('delete');
});

test('13. Staff cannot delete customer', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $customer = Customer::factory()->create();

    expect(CustomerResource::canDelete($customer))->toBeFalse();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertActionHidden('delete');
});
