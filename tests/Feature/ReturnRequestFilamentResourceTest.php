<?php

use App\Enums\ReturnRequestStatus;
use App\Filament\Resources\ReturnRequests\Pages\CreateReturnRequest;
use App\Filament\Resources\ReturnRequests\Pages\EditReturnRequest;
use App\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
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

test('can create return request', function () {
    $this->actingAs($this->admin);

    $order = Order::factory()->create(['grand_total' => 500]);
    $user = User::factory()->create();

    Livewire::test(CreateReturnRequest::class)
        ->fillForm([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => ReturnRequestStatus::Pending->value,
            'reason' => 'Defective screen',
            'admin_note' => 'Will check this one closely.',
            'refund_amount' => 500,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('return_requests', [
        'order_id' => $order->id,
        'reason' => 'Defective screen',
        'refund_amount' => 500,
    ]);
});

test('can edit return request', function () {
    $order = Order::factory()->create(['grand_total' => 100]);
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'status' => ReturnRequestStatus::Pending,
        'refund_amount' => 50,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditReturnRequest::class, ['record' => $returnRequest->id])
        ->fillForm([
            'status' => ReturnRequestStatus::Approved->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Approved);
});
