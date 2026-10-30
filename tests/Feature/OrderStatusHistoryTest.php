<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderStatusHistory;
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
    DB::table('order_status_histories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_status_histories')->truncate();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    Schema::enableForeignKeyConstraints();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/*
|--------------------------------------------------------------------------
| CAT-9E: ORDER STATUS HISTORY / AUDIT TRAIL TESTS
|--------------------------------------------------------------------------
*/

test('A. Successful transition creates exactly one history record with correct from and to status', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect($order->statusHistories()->count())->toBe(0);

    $order->confirm();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->statusHistories()->count())->toBe(1);

    /** @var OrderStatusHistory $history */
    $history = $order->statusHistories()->first();

    expect($history->from_status)->toBe(OrderStatus::Pending)
        ->and($history->to_status)->toBe(OrderStatus::Confirmed)
        ->and($history->order_id)->toBe($order->id)
        ->and($history->reason)->toBeNull()
        ->and($history->created_at)->not->toBeNull();
});

test('B. Full lifecycle creates an audit trail entry for each transition in chronological order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $order->confirm();
    $order->startProcessing();
    $order->ship();
    $order->deliver();

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->statusHistories()->count())->toBe(4);

    $histories = $order->statusHistories()->get()->sortBy('id')->values();

    expect($histories[0]->from_status)->toBe(OrderStatus::Pending)
        ->and($histories[0]->to_status)->toBe(OrderStatus::Confirmed)
        ->and($histories[1]->from_status)->toBe(OrderStatus::Confirmed)
        ->and($histories[1]->to_status)->toBe(OrderStatus::Processing)
        ->and($histories[2]->from_status)->toBe(OrderStatus::Processing)
        ->and($histories[2]->to_status)->toBe(OrderStatus::Shipped)
        ->and($histories[3]->from_status)->toBe(OrderStatus::Shipped)
        ->and($histories[3]->to_status)->toBe(OrderStatus::Delivered);
});

test('C. Cancellation transition stores from_status, cancelled to_status, user, and cancellation reason', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['status' => OrderStatus::Processing]);

    $order->cancel('Customer changed mind before dispatch', $user);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->statusHistories()->count())->toBe(1);

    /** @var OrderStatusHistory $history */
    $history = $order->statusHistories()->first();

    expect($history->from_status)->toBe(OrderStatus::Processing)
        ->and($history->to_status)->toBe(OrderStatus::Cancelled)
        ->and($history->changed_by)->toBe($user->id)
        ->and($history->reason)->toBe('Customer changed mind before dispatch')
        ->and($history->changedBy->id)->toBe($user->id);
});

test('D. Invalid transition throws exception, leaves order status unchanged, and creates no history', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    expect(fn () => $order->ship())->toThrow(InvalidOrderTransitionException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->statusHistories()->count())->toBe(0)
        ->and(DB::table('order_status_histories')->count())->toBe(0);
});

test('E. Transaction rolls back both order status and history if an error occurs during transition', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Hook into OrderStatusHistory creating event to simulate a failure after order status update
    OrderStatusHistory::creating(function () {
        throw new RuntimeException('Simulated database ledger failure');
    });

    try {
        $order->confirm();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Simulated database ledger failure');
    }

    // Verify atomic rollback
    expect(DB::table('orders')->where('id', $order->id)->value('status'))->toBe(OrderStatus::Pending->value)
        ->and(DB::table('order_status_histories')->where('order_id', $order->id)->count())->toBe(0);
});

test('F. OrderStatusHistory model is immutable and rejects update and delete operations', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $order->confirm();

    /** @var OrderStatusHistory $history */
    $history = $order->statusHistories()->first();

    // Direct update method call
    expect(fn () => $history->update(['reason' => 'Tampered reason']))
        ->toThrow(LogicException::class, 'OrderStatusHistory records are immutable and cannot be updated.');

    // Save on existing model instance
    expect(function () use ($history) {
        $history->reason = 'Direct attribute change';
        $history->save();
    })->toThrow(LogicException::class, 'OrderStatusHistory records are immutable and cannot be updated.');

    // Direct delete method call
    expect(fn () => $history->delete())
        ->toThrow(LogicException::class, 'OrderStatusHistory records are immutable and cannot be deleted.');

    // DB record remains unchanged
    expect($history->fresh()->reason)->toBeNull();
});

test('G. Authenticated user is automatically recorded as changed_by during transitions', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($admin);

    $order->confirm();

    /** @var OrderStatusHistory $history */
    $history = $order->statusHistories()->first();

    expect($history->changed_by)->toBe($admin->id)
        ->and($history->changedBy->id)->toBe($admin->id);
});

test('H. Status history is visible on the Order View page for users with orders.view authorization', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $this->actingAs($manager);
    $order->confirm();

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee('Status History')
        ->assertSee('Audit Trail')
        ->assertSee('Pending')
        ->assertSee('Confirmed')
        ->assertSee($manager->name);
});

test('I. Order View page eager loads statusHistories and changedBy avoiding N+1 queries', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // Create 5 transition records by distinct users
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create();
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending,
            'to_status' => OrderStatus::Confirmed,
            'changed_by' => $user->id,
            'reason' => "Test audit note #{$i}",
            'created_at' => now()->subMinutes(10 - $i),
        ]);
    }

    $this->actingAs($admin);

    // Track database queries during Livewire render
    $queryLog = [];
    DB::listen(function ($query) use (&$queryLog) {
        $queryLog[] = $query->sql;
    });

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertOk();

    // Verify no repetitive individual queries for users
    $userQueries = array_filter($queryLog, fn ($sql) => str_contains($sql, 'from `users` where `users`.`id` ='));
    expect(count($userQueries))->toBe(0);
});

test('J. Guest and unauthorized users cannot view order status history', function () {
    $order = Order::factory()->create();

    // Guest redirected to login
    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertRedirect('/admin/login');

    // User without can_access_admin_panel forbidden
    $regularUser = User::factory()->create(['can_access_admin_panel' => false]);
    $this->actingAs($regularUser)
        ->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertForbidden();
});
