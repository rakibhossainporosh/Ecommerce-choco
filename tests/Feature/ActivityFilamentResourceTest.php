<?php

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create(['can_access_admin_panel' => true]);
    $this->admin->assignRole('Admin');
});

test('activity logs are immutable audit records in the admin panel', function () {
    $activity = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'created',
        'subject_type' => User::class,
        'subject_id' => $this->admin->id,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
        'event' => 'created',
        'properties' => [],
    ]);

    $this->actingAs($this->admin);

    expect(ActivityResource::canCreate())->toBeFalse()
        ->and(ActivityResource::canEdit($activity))->toBeFalse()
        ->and(ActivityResource::canDelete($activity))->toBeFalse()
        ->and(ActivityResource::canDeleteAny())->toBeFalse()
        ->and(ActivityResource::getPages())->toHaveKeys(['index', 'view'])
        ->and(ActivityResource::getPages())->not->toHaveKey('edit')
        ->and(ActivityResource::getPages())->not->toHaveKey('create');
});

test('admin can list and view activity logs without edit or delete actions', function () {
    $activity = Activity::query()->create([
        'log_name' => 'default',
        'description' => 'created',
        'subject_type' => User::class,
        'subject_id' => $this->admin->id,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
        'event' => 'created',
        'properties' => [],
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListActivities::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$activity])
        ->assertTableActionExists('view')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete');

    Livewire::test(ViewActivity::class, ['record' => $activity->getRouteKey()])
        ->assertSuccessful()
        ->assertActionDoesNotExist('edit')
        ->assertActionDoesNotExist('delete');
});
