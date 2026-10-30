<?php

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    $this->seed(RolePermissionSeeder::class);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Schema::disableForeignKeyConstraints();
    Category::truncate();
    Schema::enableForeignKeyConstraints();
});

test('1. Root category can be created with parent_id = null', function () {
    $root = Category::create([
        'name' => 'Confectionery',
        'slug' => 'confectionery',
        'parent_id' => null,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect($root->exists)->toBeTrue()
        ->and($root->parent_id)->toBeNull()
        ->and($root->parent)->toBeNull();
});

test('2. Child category can be created under valid parent', function () {
    $root = Category::create([
        'name' => 'Chocolates',
        'slug' => 'chocolates',
        'parent_id' => null,
        'is_active' => true,
    ]);

    $child = Category::create([
        'name' => 'Dark Chocolate',
        'slug' => 'dark-chocolate',
        'parent_id' => $root->id,
        'is_active' => true,
    ]);

    expect($child->parent_id)->toBe($root->id)
        ->and($child->parent->name)->toBe('Chocolates');
});

test('3. Multi-level hierarchy works (Root -> Child -> Grandchild)', function () {
    $root = Category::create(['name' => 'Food & Beverage', 'slug' => 'food-beverage', 'is_active' => true]);
    $child = Category::create(['name' => 'Sweets', 'slug' => 'sweets', 'parent_id' => $root->id, 'is_active' => true]);
    $grandchild = Category::create(['name' => 'Truffles', 'slug' => 'truffles', 'parent_id' => $child->id, 'is_active' => true]);

    expect($grandchild->getAncestorIds())->toBe([$child->id, $root->id])
        ->and($root->getDescendantIds())->toContain($child->id, $grandchild->id);
});

test('4. Category cannot be its own parent at domain and UI levels', function () {
    $category = Category::create(['name' => 'Beverages', 'slug' => 'beverages', 'is_active' => true]);

    // Domain level
    expect(function () use ($category) {
        $category->parent_id = $category->id;
        $category->save();
    })->toThrow(ValidationException::class);

    // Filament Form level
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['parent_id' => $category->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

test('5. Circular hierarchy is rejected (A -> B -> C: A cannot become child of C)', function () {
    $a = Category::create(['name' => 'Category A', 'slug' => 'cat-a', 'is_active' => true]);
    $b = Category::create(['name' => 'Category B', 'slug' => 'cat-b', 'parent_id' => $a->id, 'is_active' => true]);
    $c = Category::create(['name' => 'Category C', 'slug' => 'cat-c', 'parent_id' => $b->id, 'is_active' => true]);

    // Attempt to make A a child of C (creating A -> B -> C -> A cycle)
    expect(function () use ($a, $c) {
        $a->parent_id = $c->id;
        $a->save();
    })->toThrow(ValidationException::class);

    // Filament Form level
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditCategory::class, ['record' => $a->getRouteKey()])
        ->fillForm(['parent_id' => $c->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

test('6. Category can be moved to another valid parent', function () {
    $parentOne = Category::create(['name' => 'Parent One', 'slug' => 'parent-one', 'is_active' => true]);
    $parentTwo = Category::create(['name' => 'Parent Two', 'slug' => 'parent-two', 'is_active' => true]);

    $child = Category::create(['name' => 'Movable Child', 'slug' => 'movable-child', 'parent_id' => $parentOne->id, 'is_active' => true]);

    $child->parent_id = $parentTwo->id;
    $child->save();

    expect($child->fresh()->parent_id)->toBe($parentTwo->id);
});

test('7. Category cannot be moved under its direct or indirect descendant', function () {
    $root = Category::create(['name' => 'Root Cat', 'slug' => 'root-cat', 'is_active' => true]);
    $child = Category::create(['name' => 'Child Cat', 'slug' => 'child-cat', 'parent_id' => $root->id, 'is_active' => true]);
    $grandchild = Category::create(['name' => 'Grandchild Cat', 'slug' => 'grandchild-cat', 'parent_id' => $child->id, 'is_active' => true]);

    // Move root under child
    expect(function () use ($root, $child) {
        $root->parent_id = $child->id;
        $root->save();
    })->toThrow(ValidationException::class);

    // Move root under grandchild
    expect(function () use ($root, $grandchild) {
        $root->parent_id = $grandchild->id;
        $root->save();
    })->toThrow(ValidationException::class);
});

test('8. Category with children cannot be deleted via softDelete or forceDelete', function () {
    $parent = Category::create(['name' => 'Parent With Child', 'slug' => 'parent-with-child', 'is_active' => true]);
    Category::create(['name' => 'Child Node', 'slug' => 'child-node', 'parent_id' => $parent->id, 'is_active' => true]);

    // Direct model soft delete throws DomainException
    expect(function () use ($parent) {
        $parent->delete();
    })->toThrow(DomainException::class, 'Cannot delete this category because it has child categories.');

    expect($parent->fresh()->trashed())->toBeFalse();

    // Direct model forceDelete also throws DomainException
    expect(function () use ($parent) {
        $parent->forceDelete();
    })->toThrow(DomainException::class, 'Cannot delete this category because it has child categories.');

    expect(Category::withTrashed()->find($parent->id))->not->toBeNull();

    // Filament Edit Action rejects and halts
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditCategory::class, ['record' => $parent->getRouteKey()])
        ->callAction('delete')
        ->assertNotDispatched('close-modal');

    expect($parent->fresh()->trashed())->toBeFalse();
});

test('9. Category without children can be soft deleted and force deleted', function () {
    $leaf = Category::create(['name' => 'Leaf Category', 'slug' => 'leaf-category', 'is_active' => true]);

    expect($leaf->canBeDeleted())->toBeTrue();

    $leaf->delete();

    expect($leaf->fresh()->trashed())->toBeTrue();

    // Leaf category can also be force deleted once it has no children
    $leaf->forceDelete();
    expect(Category::withTrashed()->find($leaf->id))->toBeNull();
});

test('10. Soft-deleted category cannot be selected as parent', function () {
    $deletedParent = Category::create(['name' => 'Deleted Parent', 'slug' => 'deleted-parent', 'is_active' => true]);
    $deletedParent->delete();

    expect(function () use ($deletedParent) {
        Category::create([
            'name' => 'New Orphan',
            'slug' => 'new-orphan',
            'parent_id' => $deletedParent->id,
            'is_active' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('11. Active category cannot use inactive parent', function () {
    $inactiveParent = Category::create(['name' => 'Inactive Parent', 'slug' => 'inactive-parent', 'is_active' => false]);

    expect(function () use ($inactiveParent) {
        Category::create([
            'name' => 'Active Child',
            'slug' => 'active-child',
            'parent_id' => $inactiveParent->id,
            'is_active' => true,
        ]);
    })->toThrow(ValidationException::class);
});

test('12. Active child cannot be activated under an inactive parent', function () {
    $inactiveParent = Category::create(['name' => 'Parent Shelf', 'slug' => 'parent-shelf', 'is_active' => false]);
    $child = Category::create(['name' => 'Child Shelf', 'slug' => 'child-shelf', 'parent_id' => $inactiveParent->id, 'is_active' => false]);

    expect(function () use ($child) {
        $child->is_active = true;
        $child->save();
    })->toThrow(ValidationException::class);
});

test('13. Category with active children cannot be deactivated', function () {
    $parent = Category::create(['name' => 'Active Parent', 'slug' => 'active-parent', 'is_active' => true]);
    Category::create(['name' => 'Active Sub', 'slug' => 'active-sub', 'parent_id' => $parent->id, 'is_active' => true]);

    expect(function () use ($parent) {
        $parent->is_active = false;
        $parent->save();
    })->toThrow(ValidationException::class);

    // Filament form level
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    Livewire::test(EditCategory::class, ['record' => $parent->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasFormErrors(['is_active']);
});

test('14. Category can be deactivated when it has no active children', function () {
    $parent = Category::create(['name' => 'Parent With Inactive Child', 'slug' => 'parent-inactive-child', 'is_active' => true]);
    Category::create(['name' => 'Inactive Sub', 'slug' => 'inactive-sub', 'parent_id' => $parent->id, 'is_active' => false]);

    $parent->is_active = false;
    $parent->save();

    expect($parent->fresh()->is_active)->toBeFalse();
});

test('15. Duplicate root category name is rejected', function () {
    Category::create(['name' => 'Gourmet Treats', 'slug' => 'gourmet-treats-1', 'parent_id' => null, 'is_active' => true]);

    expect(function () {
        Category::create(['name' => '  Gourmet Treats  ', 'slug' => 'gourmet-treats-2', 'parent_id' => null, 'is_active' => true]);
    })->toThrow(ValidationException::class);
});

test('16. Duplicate child name under same parent is rejected', function () {
    $parent = Category::create(['name' => 'Gift Hampers', 'slug' => 'gift-hampers', 'is_active' => true]);
    Category::create(['name' => 'Luxury Hamper', 'slug' => 'luxury-hamper-1', 'parent_id' => $parent->id, 'is_active' => true]);

    expect(function () use ($parent) {
        Category::create(['name' => 'Luxury Hamper', 'slug' => 'luxury-hamper-2', 'parent_id' => $parent->id, 'is_active' => true]);
    })->toThrow(ValidationException::class);
});

test('17. Same name under different parents is allowed', function () {
    $parentOne = Category::create(['name' => 'Dark Collections', 'slug' => 'dark-collections', 'is_active' => true]);
    $parentTwo = Category::create(['name' => 'Milk Collections', 'slug' => 'milk-collections', 'is_active' => true]);

    $itemOne = Category::create(['name' => 'Gift Box', 'slug' => 'gift-box-dark', 'parent_id' => $parentOne->id, 'is_active' => true]);
    $itemTwo = Category::create(['name' => 'Gift Box', 'slug' => 'gift-box-milk', 'parent_id' => $parentTwo->id, 'is_active' => true]);

    expect($itemOne->exists)->toBeTrue()
        ->and($itemTwo->exists)->toBeTrue()
        ->and($itemOne->name)->toBe($itemTwo->name)
        ->and($itemOne->parent_id)->not->toBe($itemTwo->parent_id);
});

test('18. Slug remains unchanged when name changes', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::create(['name' => 'Original Artisan Bar', 'slug' => 'original-artisan-bar', 'is_active' => true]);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name' => 'Renamed Artisan Bar',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->name)->toBe('Renamed Artisan Bar')
        ->and($category->fresh()->slug)->toBe('original-artisan-bar');
});

test('19. Duplicate slug remains rejected by database unique index', function () {
    Category::create(['name' => 'Category Unique', 'slug' => 'unique-slug', 'is_active' => true]);

    expect(function () {
        Category::create(['name' => 'Another Category', 'slug' => 'unique-slug', 'is_active' => true]);
    })->toThrow(QueryException::class);
});

test('20. Moving category preserves unrelated hierarchy branches', function () {
    $branchA = Category::create(['name' => 'Branch A', 'slug' => 'branch-a', 'is_active' => true]);
    $leafA = Category::create(['name' => 'Leaf A', 'slug' => 'leaf-a', 'parent_id' => $branchA->id, 'is_active' => true]);

    $branchB = Category::create(['name' => 'Branch B', 'slug' => 'branch-b', 'is_active' => true]);
    $leafB = Category::create(['name' => 'Leaf B', 'slug' => 'leaf-b', 'parent_id' => $branchB->id, 'is_active' => true]);

    // Move leafA to root
    $leafA->parent_id = null;
    $leafA->save();

    // Verify branchB and leafB hierarchy remains unchanged
    expect($branchB->fresh()->children)->toHaveCount(1)
        ->and($branchB->fresh()->children->first()->id)->toBe($leafB->id)
        ->and($leafA->fresh()->parent_id)->toBeNull();
});

test('21. Deleted parent relationship does not become a valid new parent', function () {
    $parent = Category::create(['name' => 'Temporary Parent', 'slug' => 'temp-parent', 'is_active' => true]);
    $parent->delete();

    $child = Category::create(['name' => 'Stray Child', 'slug' => 'stray-child', 'is_active' => true]);

    expect(function () use ($child, $parent) {
        $child->parent_id = $parent->id;
        $child->save();
    })->toThrow(ValidationException::class);
});

test('22. Product-delete rule is explicitly documented as deferred until Product model exists', function () {
    // Assert that Category::canBeDeleted() is structurally designed to incorporate future Product verification
    $category = Category::create(['name' => 'Stand-Alone Category', 'slug' => 'standalone-category', 'is_active' => true]);

    expect(method_exists($category, 'canBeDeleted'))->toBeTrue()
        ->and($category->canBeDeleted())->toBeTrue();
});
