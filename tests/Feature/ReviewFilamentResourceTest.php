<?php

use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Product;
use App\Models\Review;
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

test('can list reviews', function () {
    Review::factory(3)->create();

    $this->actingAs($this->admin);

    Livewire::test(ListReviews::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Review::all());
});

test('can create review', function () {
    $this->actingAs($this->admin);

    $product = Product::factory()->create();
    $user = User::factory()->create();

    Livewire::test(CreateReview::class)
        ->fillForm([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Excellent!',
            'body' => 'I loved it.',
            'is_approved' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'rating' => 5,
        'title' => 'Excellent!',
    ]);
});

test('can edit review', function () {
    $review = Review::factory()->create(['title' => 'Old Title']);

    $this->actingAs($this->admin);

    Livewire::test(EditReview::class, ['record' => $review->id])
        ->fillForm([
            'title' => 'Updated Title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($review->fresh()->title)->toBe('Updated Title');
});
