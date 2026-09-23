<?php

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');
    DB::purge('mysql');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    Category::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
});

test('1. Category can be created with explicit attributes', function () {
    $category = Category::create([
        'name' => 'Dark Chocolate',
        'slug' => 'dark-chocolate',
        'description' => 'Rich and intense dark chocolate bars.',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->id)->toBeGreaterThan(0)
        ->and($category->name)->toBe('Dark Chocolate')
        ->and($category->slug)->toBe('dark-chocolate')
        ->and($category->description)->toBe('Rich and intense dark chocolate bars.')
        ->and($category->is_active)->toBeTrue()
        ->and($category->sort_order)->toBe(10)
        ->and($category->parent_id)->toBeNull();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'slug' => 'dark-chocolate',
    ]);
});

test('2. Category defaults: is_active defaults to true and sort_order defaults to 0', function () {
    $category = Category::create([
        'name' => 'Milk Chocolate',
        'slug' => 'milk-chocolate',
    ]);

    // Fresh instance from database to verify database default values
    $category->refresh();

    expect($category->is_active)->toBeTrue()
        ->and($category->sort_order)->toBe(0)
        ->and($category->parent_id)->toBeNull()
        ->and($category->description)->toBeNull();
});

test('3. Slug uniqueness is enforced by the database', function () {
    Category::create([
        'name' => 'Truffles',
        'slug' => 'truffles',
    ]);

    expect(function () {
        Category::create([
            'name' => 'Duplicate Truffles',
            'slug' => 'truffles',
        ]);
    })->toThrow(QueryException::class);
});

test('4. Root category has parent_id = null', function () {
    $root = Category::factory()->create([
        'name' => 'Chocolates',
        'parent_id' => null,
    ]);

    expect($root->parent_id)->toBeNull()
        ->and($root->parent)->toBeNull();
});

test('5. Child category can reference a parent', function () {
    $parent = Category::factory()->create(['name' => 'Chocolate Bars']);
    $child = Category::factory()->create([
        'name' => 'White Chocolate',
        'parent_id' => $parent->id,
    ]);

    expect($child->parent_id)->toBe($parent->id);
    $this->assertDatabaseHas('categories', [
        'id' => $child->id,
        'parent_id' => $parent->id,
    ]);
});

test('6. Parent relationship works (belongsTo)', function () {
    $parent = Category::factory()->create(['name' => 'Chocolate Bars']);
    $child = Category::factory()->create([
        'name' => 'Dark Chocolate Bars',
        'parent_id' => $parent->id,
    ]);

    expect($child->parent)->not->toBeNull()
        ->and($child->parent)->toBeInstanceOf(Category::class)
        ->and($child->parent->id)->toBe($parent->id)
        ->and($child->parent->name)->toBe('Chocolate Bars');
});

test('7. Children relationship works (hasMany)', function () {
    $parent = Category::factory()->create(['name' => 'Gift Boxes']);
    $child = Category::factory()->create([
        'name' => 'Assorted Gift Box',
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->id)->toBe($child->id)
        ->and($parent->children->first()->name)->toBe('Assorted Gift Box');
});

test('8. Multiple children can belong to one parent', function () {
    $parent = Category::factory()->create(['name' => 'Bakery & Confectionery']);
    $child1 = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Brownies', 'sort_order' => 1]);
    $child2 = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Cookies', 'sort_order' => 2]);
    $child3 = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Cakes', 'sort_order' => 3]);

    $parent->refresh();

    expect($parent->children)->toHaveCount(3)
        ->and($parent->children->pluck('id')->all())->toBe([$child1->id, $child2->id, $child3->id]);
});

test('9. Category supports multiple hierarchy levels (Root -> Child -> Grandchild)', function () {
    $root = Category::factory()->create(['name' => 'Confectionery', 'parent_id' => null]);
    $child = Category::factory()->create(['name' => 'Chocolates', 'parent_id' => $root->id]);
    $grandchild = Category::factory()->create(['name' => 'Artisan Dark Chocolate', 'parent_id' => $child->id]);

    expect($grandchild->parent->id)->toBe($child->id)
        ->and($grandchild->parent->parent->id)->toBe($root->id)
        ->and($root->children->first()->id)->toBe($child->id)
        ->and($child->children->first()->id)->toBe($grandchild->id);
});

test('10. Soft delete works and sets deleted_at timestamp', function () {
    $category = Category::factory()->create();

    expect($category->deleted_at)->toBeNull();

    $category->delete();

    expect($category->trashed())->toBeTrue()
        ->and($category->deleted_at)->not->toBeNull();

    $this->assertSoftDeleted('categories', [
        'id' => $category->id,
    ]);
});

test('11. Soft-deleted category is excluded from normal queries', function () {
    $category = Category::factory()->create(['name' => 'Seasonal Special']);
    $category->delete();

    expect(Category::where('name', 'Seasonal Special')->first())->toBeNull()
        ->and(Category::find($category->id))->toBeNull();
});

test('12. restore() works and clears deleted_at', function () {
    $category = Category::factory()->create();
    $category->delete();

    expect($category->trashed())->toBeTrue();

    $category->restore();

    expect($category->fresh()->trashed())->toBeFalse()
        ->and($category->fresh()->deleted_at)->toBeNull();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'deleted_at' => null,
    ]);
});

test('13. withTrashed() can retrieve soft-deleted category', function () {
    $category = Category::factory()->create(['name' => 'Holiday Pack']);
    $category->delete();

    $retrieved = Category::withTrashed()->find($category->id);

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->id)->toBe($category->id)
        ->and($retrieved->trashed())->toBeTrue();
});

test('14. Factory creates valid categories', function () {
    $categories = Category::factory()->count(5)->create();

    expect($categories)->toHaveCount(5);

    foreach ($categories as $cat) {
        expect($cat->name)->toBeString()->not->toBeEmpty()
            ->and($cat->slug)->toBeString()->not->toBeEmpty()
            ->and($cat->is_active)->toBeTrue()
            ->and($cat->sort_order)->toBe(0);
    }
});

test('15. Foreign key ON DELETE SET NULL sets child parent_id to null when parent is force-deleted', function () {
    $parent = Category::factory()->create(['name' => 'Parent Group']);
    $child = Category::factory()->create([
        'name' => 'Child Category',
        'parent_id' => $parent->id,
    ]);

    expect($child->parent_id)->toBe($parent->id);

    // When the parent is permanently deleted from the database schema
    $parent->forceDeleteQuietly();

    $child->refresh();

    expect($child->parent_id)->toBeNull()
        ->and($child->parent)->toBeNull();
});

test('16. Attribute casting ensures proper boolean and integer types', function () {
    $parent = Category::factory()->create();

    $category = Category::create([
        'name' => 'Cast Test',
        'slug' => 'cast-test',
        'is_active' => 1,
        'sort_order' => '15',
        'parent_id' => (string) $parent->id,
    ]);

    expect($category->is_active)->toBeTrue()
        ->and($category->sort_order)->toBe(15)
        ->and($category->parent_id)->toBe($parent->id);
});
