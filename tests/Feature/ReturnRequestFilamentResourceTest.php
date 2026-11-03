<?php

use App\Enums\OrderStatus;
use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ReturnRequestsRelationManager;
use App\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
use App\Filament\Resources\ReturnRequests\Pages\ViewReturnRequest;
use App\Filament\Resources\ReturnRequests\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('can list return requests', function () {
    ReturnRequest::factory(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(ListReturnRequests::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(ReturnRequest::all());
});

test('can create return request via modal', function () {
    $this->actingAs($this->admin);

    $order = Order::factory()->create(['grand_total' => 500, 'status' => OrderStatus::Delivered]);
    $user = User::factory()->create();

    Livewire::test(ListReturnRequests::class)
        ->callAction('create', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Defective screen',
            'admin_note' => 'Will check this one closely.',
            'refund_amount' => 500,
            'line_items' => [],
        ])
        ->assertHasNoActionErrors();

    $this->assertDatabaseHas('return_requests', [
        'order_id' => $order->id,
        'reason' => 'Defective screen',
        'refund_amount' => 500,
    ]);
});

test('can approve return request via lifecycle action', function () {
    $order = Order::factory()->create(['grand_total' => 100, 'status' => OrderStatus::Delivered]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 50,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewReturnRequest::class, ['record' => $returnRequest->id])
        ->callAction('approve')
        ->assertHasNoActionErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Approved);
});

test('return request resource disables free edit and completed delete', function () {
    $this->actingAs($this->admin);

    $pending = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);
    $completed = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Completed]);

    expect(ReturnRequestResource::canEdit($pending))->toBeFalse()
        ->and(ReturnRequestResource::canDelete($pending))->toBeTrue()
        ->and(ReturnRequestResource::canDelete($completed))->toBeFalse();
});

test('return request resource displays navigation badge for pending requests', function () {
    expect(ReturnRequestResource::getNavigationBadge())->toBeNull();

    ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);
    ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);
    ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Approved]);

    expect(ReturnRequestResource::getNavigationBadge())->toBe('2')
        ->and(ReturnRequestResource::getNavigationBadgeColor())->toBe('warning')
        ->and(ReturnRequestResource::getNavigationBadgeTooltip())->toBe('Pending return requests');
});

test('list return requests renders status filter tabs with badges', function () {
    $this->actingAs($this->admin);

    ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);
    ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Approved]);

    $component = Livewire::test(ListReturnRequests::class);
    $tabs = $component->instance()->getTabs();

    expect($tabs)->toHaveKeys(['all', 'pending', 'approved', 'received', 'completed', 'rejected', 'cancelled'])
        ->and((string) $tabs['pending']->getBadge())->toBe('1')
        ->and((string) $tabs['approved']->getBadge())->toBe('1')
        ->and((string) $tabs['all']->getBadge())->toBe('2');
});

test('admin can approve, receive, reject, and complete return request via actions', function () {
    $this->actingAs($this->admin);

    $returnRequest = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);

    Livewire::test(ViewReturnRequest::class, ['record' => $returnRequest->getRouteKey()])
        ->assertActionVisible('approve')
        ->callAction('approve')
        ->assertHasNoActionErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Approved);

    Livewire::test(ViewReturnRequest::class, ['record' => $returnRequest->getRouteKey()])
        ->assertActionVisible('markReceived')
        ->callAction('markReceived')
        ->assertHasNoActionErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Received);

    Livewire::test(ViewReturnRequest::class, ['record' => $returnRequest->getRouteKey()])
        ->assertActionVisible('complete')
        ->callAction('complete')
        ->assertHasNoActionErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Completed);

    $newRequest = ReturnRequest::factory()->create(['status' => ReturnRequestStatus::Pending]);

    Livewire::test(ViewReturnRequest::class, ['record' => $newRequest->getRouteKey()])
        ->assertActionVisible('reject')
        ->callAction('reject', [
            'admin_note' => 'Policy violation: opened seal',
        ])
        ->assertHasNoActionErrors();

    expect($newRequest->fresh()->status)->toBe(ReturnRequestStatus::Rejected)
        ->and($newRequest->fresh()->admin_note)->toBe('Policy violation: opened seal');
});

test('ReturnRequestsRelationManager is registered in OrderResource relations', function () {
    expect(OrderResource::getRelations())
        ->toContain(ReturnRequestsRelationManager::class);
});

test('ReturnRequestsRelationManager renders on order view page and supports actions', function () {
    $this->actingAs($this->admin);

    $order = Order::factory()->create(['grand_total' => 1200, 'status' => OrderStatus::Delivered]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $this->admin->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 500,
        'reason' => 'Damaged chocolate box',
    ]);

    Livewire::test(ReturnRequestsRelationManager::class, [
        'ownerRecord' => $order,
        'pageClass' => ViewOrder::class,
    ])
        ->assertCanSeeTableRecords([$returnRequest])
        ->assertTableActionVisible('approve', $returnRequest)
        ->callTableAction('approve', $returnRequest)
        ->assertHasNoTableActionErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Approved);
});
