<?php

use App\Filament\Resources\Attributes\AttributeResource;
use App\Filament\Resources\Attributes\Pages\CreateAttribute;
use App\Filament\Resources\Attributes\Pages\EditAttribute;
use App\Filament\Resources\Attributes\RelationManagers\ValuesRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
    DB::table('variant_attribute_values')->truncate();
    DB::table('product_attribute_values')->truncate();
    DB::table('attribute_values')->truncate();
    DB::table('attributes')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

// =========================================================================
// Group 1: Attribute Resource (Tests 1 - 10)
// =========================================================================

test('1. guest cannot access AttributeResource', function () {
    $this->get(AttributeResource::getUrl('index'))
        ->assertRedirect('/admin/login');
});

test('2. user without products.view cannot view AttributeResource', function () {
    $user = User::factory()->create(['can_access_admin_panel' => true]);
    $this->actingAs($user);

    $this->get(AttributeResource::getUrl('index'))
        ->assertForbidden();

    expect(AttributeResource::canAccess())->toBeFalse()
        ->and(AttributeResource::canViewAny())->toBeFalse();
});

test('3. products.view user can view AttributeResource', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $this->get(AttributeResource::getUrl('index'))
        ->assertOk();

    expect(AttributeResource::canAccess())->toBeTrue()
        ->and(AttributeResource::canViewAny())->toBeTrue();
});

test('4. products.view-only user cannot create attributes', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $this->get(AttributeResource::getUrl('create'))
        ->assertForbidden();

    expect(AttributeResource::canCreate())->toBeFalse();
});

test('5. products.view-only user cannot edit attributes', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $attribute = Attribute::factory()->create();

    $this->get(AttributeResource::getUrl('edit', ['record' => $attribute]))
        ->assertForbidden();

    expect(AttributeResource::canEdit($attribute))->toBeFalse();
});

test('6. products.update user can create attributes', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    expect(AttributeResource::canCreate())->toBeTrue();

    Livewire::test(CreateAttribute::class)
        ->fillForm([
            'name' => 'Cocoa Percentage',
            'slug' => 'cocoa-percentage',
            'type' => Attribute::ATTRIBUTE_TYPE_NUMBER,
            'scope' => Attribute::SCOPE_PRODUCT,
            'description' => 'Percentage of pure cocoa mass',
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('attributes', [
        'name' => 'Cocoa Percentage',
        'slug' => 'cocoa-percentage',
        'type' => 'number',
        'scope' => 'product',
    ]);
});

test('7. products.update user can edit attributes', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $attribute = Attribute::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    expect(AttributeResource::canEdit($attribute))->toBeTrue();

    Livewire::test(EditAttribute::class, ['record' => $attribute->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Attribute Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($attribute->fresh()->name)->toBe('Updated Attribute Name');
});

test('8. Admin has full access to AttributeResource', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $attribute = Attribute::factory()->create();

    expect(AttributeResource::canAccess())->toBeTrue()
        ->and(AttributeResource::canViewAny())->toBeTrue()
        ->and(AttributeResource::canCreate())->toBeTrue()
        ->and(AttributeResource::canEdit($attribute))->toBeTrue()
        ->and(AttributeResource::canDelete($attribute))->toBeTrue();

    $this->get(AttributeResource::getUrl('index'))->assertOk();
    $this->get(AttributeResource::getUrl('create'))->assertOk();
    $this->get(AttributeResource::getUrl('edit', ['record' => $attribute]))->assertOk();
});

test('9. Manager has allowed access to AttributeResource', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $attribute = Attribute::factory()->create();

    expect(AttributeResource::canAccess())->toBeTrue()
        ->and(AttributeResource::canViewAny())->toBeTrue()
        ->and(AttributeResource::canCreate())->toBeTrue()
        ->and(AttributeResource::canEdit($attribute))->toBeTrue();
});

test('10. Staff remains read-only on AttributeResource', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $attribute = Attribute::factory()->create();

    expect(AttributeResource::canAccess())->toBeTrue()
        ->and(AttributeResource::canViewAny())->toBeTrue()
        ->and(AttributeResource::canCreate())->toBeFalse()
        ->and(AttributeResource::canEdit($attribute))->toBeFalse()
        ->and(AttributeResource::canDelete($attribute))->toBeFalse();
});

// =========================================================================
// Group 2: Attribute Value Management (Tests 11 - 15)
// =========================================================================

test('11. select attribute shows values management', function () {
    $selectAttr = Attribute::factory()->select()->create();

    expect(ValuesRelationManager::canViewForRecord($selectAttr, EditAttribute::class))->toBeTrue();
});

test('12. multiselect attribute shows values management', function () {
    $multiAttr = Attribute::factory()->multiselect()->create();

    expect(ValuesRelationManager::canViewForRecord($multiAttr, EditAttribute::class))->toBeTrue();
});

test('13. text attribute does not expose value creation', function () {
    $textAttr = Attribute::factory()->text()->create();
    $textareaAttr = Attribute::factory()->create(['type' => Attribute::ATTRIBUTE_TYPE_TEXTAREA]);
    $numberAttr = Attribute::factory()->number()->create();
    $booleanAttr = Attribute::factory()->boolean()->create();

    expect(ValuesRelationManager::canViewForRecord($textAttr, EditAttribute::class))->toBeFalse()
        ->and(ValuesRelationManager::canViewForRecord($textareaAttr, EditAttribute::class))->toBeFalse()
        ->and(ValuesRelationManager::canViewForRecord($numberAttr, EditAttribute::class))->toBeFalse()
        ->and(ValuesRelationManager::canViewForRecord($booleanAttr, EditAttribute::class))->toBeFalse();
});

test('14. inactive values unavailable for new selection', function () {
    $attr = Attribute::factory()->select()->productScope()->create();
    $activeVal = AttributeValue::factory()->create([
        'attribute_id' => $attr->id,
        'name' => 'Active Bean Type',
        'is_active' => true,
    ]);
    $inactiveVal = AttributeValue::factory()->inactive()->create([
        'attribute_id' => $attr->id,
        'name' => 'Discontinued Bean Type',
        'is_active' => false,
    ]);

    $field = ProductForm::createAttributeField($attr, 'product_attributes');
    $options = $field->getOptions();

    expect($options)->toHaveKey($activeVal->id)
        ->and($options)->not->toHaveKey($inactiveVal->id);
});

test('15. soft-deleted values unavailable for new selection', function () {
    $attr = Attribute::factory()->select()->productScope()->create();
    $activeVal = AttributeValue::factory()->create([
        'attribute_id' => $attr->id,
        'name' => 'Active Bean Type',
    ]);
    $deletedVal = AttributeValue::factory()->create([
        'attribute_id' => $attr->id,
        'name' => 'Deleted Bean Type',
    ]);
    $deletedVal->delete();

    $field = ProductForm::createAttributeField($attr, 'product_attributes');
    $options = $field->getOptions();

    expect($options)->toHaveKey($activeVal->id)
        ->and($options)->not->toHaveKey($deletedVal->id);
});

// =========================================================================
// Group 3: Product Attribute UI (Tests 16 - 29)
// =========================================================================

test('16. only active product-scope Attributes appear', function () {
    $activeAttr = Attribute::factory()->productScope()->create(['is_active' => true]);
    $inactiveAttr = Attribute::factory()->productScope()->create(['is_active' => false]);
    $deletedAttr = Attribute::factory()->productScope()->create(['is_active' => true]);
    $deletedAttr->delete();

    $components = ProductForm::getProductAttributeComponents();
    $names = array_map(fn ($c) => $c->getName(), $components);

    expect($names)->toContain("product_attributes.{$activeAttr->id}")
        ->and($names)->not->toContain("product_attributes.{$inactiveAttr->id}")
        ->and($names)->not->toContain("product_attributes.{$deletedAttr->id}");
});

test('17. variant-scope Attributes do not appear in product attributes section', function () {
    $variantAttr = Attribute::factory()->variantScope()->create(['is_active' => true]);

    $components = ProductForm::getProductAttributeComponents();
    $names = array_map(fn ($c) => $c->getName(), $components);

    expect($names)->not->toContain("product_attributes.{$variantAttr->id}");
});

test('18. correct field generated for text', function () {
    $attr = Attribute::factory()->productScope()->text()->create();
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->isNumeric())->toBeFalse();
});

test('19. correct field generated for textarea', function () {
    $attr = Attribute::factory()->productScope()->create(['type' => Attribute::ATTRIBUTE_TYPE_TEXTAREA]);
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(Textarea::class);
});

test('20. correct field generated for number', function () {
    $attr = Attribute::factory()->productScope()->number()->create();
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(TextInput::class)
        ->and($field->isNumeric())->toBeTrue();
});

test('21. correct field generated for boolean', function () {
    $attr = Attribute::factory()->productScope()->boolean()->create();
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(Toggle::class);
});

test('22. correct field generated for select', function () {
    $attr = Attribute::factory()->productScope()->select()->create();
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->isMultiple())->toBeFalse();
});

test('23. correct field generated for multiselect', function () {
    $attr = Attribute::factory()->productScope()->multiselect()->create();
    $field = ProductForm::createAttributeField($attr, 'product_attributes');

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->isMultiple())->toBeTrue();
});

test('24. required Attribute gets required UI validation', function () {
    $reqAttr = Attribute::factory()->productScope()->text()->create(['is_required' => true]);
    $optAttr = Attribute::factory()->productScope()->text()->create(['is_required' => false]);

    $reqField = ProductForm::createAttributeField($reqAttr, 'product_attributes');
    $optField = ProductForm::createAttributeField($optAttr, 'product_attributes');

    expect($reqField->isRequired())->toBeTrue()
        ->and($optField->isRequired())->toBeFalse();
});

test('25. Product attribute save uses domain sync', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);
    $attr = Attribute::factory()->productScope()->text()->create(['is_required' => false]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Artisan Single Origin',
            'slug' => 'artisan-single-origin',
            'categories' => [$category->id],
            'unit_id' => $unit->id,
            'sku' => 'SKU-SINGLE-01',
            'cost_price' => '50.00',
            'selling_price' => '90.00',
            'unit_quantity' => '1.000',
            "product_attributes.{$attr->id}" => 'Madagascar Sambirano',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'artisan-single-origin')->first();
    expect($product)->not->toBeNull()
        ->and($product->productAttributeValues)->toHaveCount(1)
        ->and($product->productAttributeValues->first()->text_value)->toBe('Madagascar Sambirano');
});

test('26. optional Attribute removal works', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $attr = Attribute::factory()->productScope()->text()->create(['is_required' => false]);
    $product->syncAttributes([$attr->id => 'Initial Origin']);
    expect($product->fresh()->productAttributeValues)->toHaveCount(1);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => $product->name,
            'slug' => $product->slug,
            'categories' => [$category->id],
            "product_attributes.{$attr->id}" => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->productAttributeValues)->toHaveCount(0);
});

test('27. required Attribute removal fails', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_active' => true]);
    $product->categories()->attach($category->id);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'is_default' => true,
        'is_active' => true,
    ]);

    $reqAttr = Attribute::factory()->productScope()->text()->create(['is_required' => true]);
    $product->syncAttributes([$reqAttr->id => 'Required Value']);
    expect($product->fresh()->productAttributeValues)->toHaveCount(1);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => $product->name,
            'slug' => $product->slug,
            'categories' => [$category->id],
            "product_attributes.{$reqAttr->id}" => null,
        ])
        ->call('save')
        ->assertHasFormErrors(["product_attributes.{$reqAttr->id}" => 'required']);

    // Atomicity preserved
    expect($product->fresh()->productAttributeValues)->toHaveCount(1);
});

test('28. invalid AttributeValue is rejected', function () {
    $product = Product::factory()->create();
    $attr1 = Attribute::factory()->productScope()->select()->create();
    $attr2 = Attribute::factory()->productScope()->select()->create();
    $foreignVal = AttributeValue::factory()->create(['attribute_id' => $attr2->id]);

    expect(function () use ($product, $attr1, $foreignVal) {
        $product->syncAttributes([
            $attr1->id => $foreignVal->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('29. wrong scope cannot be assigned', function () {
    $product = Product::factory()->create();
    $variantAttr = Attribute::factory()->variantScope()->text()->create();

    expect(function () use ($product, $variantAttr) {
        $product->syncAttributes([
            $variantAttr->id => 'Wrong Scope Text',
        ]);
    })->toThrow(ValidationException::class);
});

// =========================================================================
// Group 4: Variant Attribute UI (Tests 30 - 35)
// =========================================================================

test('30. only active variant-scope Attributes appear', function () {
    $activeVarAttr = Attribute::factory()->variantScope()->create(['is_active' => true]);
    $inactiveVarAttr = Attribute::factory()->variantScope()->create(['is_active' => false]);
    $deletedVarAttr = Attribute::factory()->variantScope()->create(['is_active' => true]);
    $deletedVarAttr->delete();

    $components = ProductForm::getVariantAttributeComponents();
    $names = array_map(fn ($c) => $c->getName(), $components);

    expect($names)->toContain("variant_attributes.{$activeVarAttr->id}")
        ->and($names)->not->toContain("variant_attributes.{$inactiveVarAttr->id}")
        ->and($names)->not->toContain("variant_attributes.{$deletedVarAttr->id}");
});

test('31. product-scope Attributes do not appear in variant attributes', function () {
    $prodAttr = Attribute::factory()->productScope()->create(['is_active' => true]);

    $components = ProductForm::getVariantAttributeComponents();
    $names = array_map(fn ($c) => $c->getName(), $components);

    expect($names)->not->toContain("variant_attributes.{$prodAttr->id}");
});

test('32. correct field types for variant attributes', function () {
    $textAttr = Attribute::factory()->variantScope()->text()->create();
    $numAttr = Attribute::factory()->variantScope()->number()->create();
    $boolAttr = Attribute::factory()->variantScope()->boolean()->create();
    $selectAttr = Attribute::factory()->variantScope()->select()->create();
    $multiAttr = Attribute::factory()->variantScope()->multiselect()->create();

    expect(ProductForm::createAttributeField($textAttr, 'variant_attributes'))->toBeInstanceOf(TextInput::class)
        ->and(ProductForm::createAttributeField($numAttr, 'variant_attributes')->isNumeric())->toBeTrue()
        ->and(ProductForm::createAttributeField($boolAttr, 'variant_attributes'))->toBeInstanceOf(Toggle::class)
        ->and(ProductForm::createAttributeField($selectAttr, 'variant_attributes')->isMultiple())->toBeFalse()
        ->and(ProductForm::createAttributeField($multiAttr, 'variant_attributes')->isMultiple())->toBeTrue();
});

test('33. required Variant Attribute enforced during product creation', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $category = Category::factory()->create(['is_active' => true]);
    $unit = Unit::factory()->create(['is_active' => true]);
    $reqVarAttr = Attribute::factory()->variantScope()->text()->create(['is_required' => true]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Failing Variant Creation',
            'slug' => 'failing-variant-creation',
            'categories' => [$category->id],
            'unit_id' => $unit->id,
            'sku' => 'SKU-FAIL-01',
            'cost_price' => '50.00',
            'selling_price' => '90.00',
            'unit_quantity' => '1.000',
            // required variant attribute omitted
        ])
        ->call('create')
        ->assertHasFormErrors(["variant_attributes.{$reqVarAttr->id}" => 'required']);

    expect(Product::where('slug', 'failing-variant-creation')->exists())->toBeFalse();
});

test('34. invalid AttributeValue rejected for variant', function () {
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $attr1 = Attribute::factory()->variantScope()->select()->create();
    $attr2 = Attribute::factory()->variantScope()->select()->create();
    $foreignVal = AttributeValue::factory()->create(['attribute_id' => $attr2->id]);

    expect(function () use ($variant, $attr1, $foreignVal) {
        $variant->syncAttributes([
            $attr1->id => $foreignVal->id,
        ]);
    })->toThrow(ValidationException::class);
});

test('35. wrong scope rejected for variant attribute assignment', function () {
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    $prodAttr = Attribute::factory()->productScope()->text()->create();

    expect(function () use ($variant, $prodAttr) {
        $variant->syncAttributes([
            $prodAttr->id => 'Wrong Scope',
        ]);
    })->toThrow(ValidationException::class);
});

// =========================================================================
// Group 5: Authorization Matrix (Tests 36 - 40)
// =========================================================================

test('36. Staff cannot mutate Product attributes', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $product = Product::factory()->create();

    expect(ProductResource::canEdit($product))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('update', $product))->toBeFalse();

    $this->get(ProductResource::getUrl('edit', ['record' => $product]))
        ->assertForbidden();
});

test('37. Staff cannot mutate Variant attributes', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    expect(Gate::forUser($staff)->allows('update', $variant))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('create', ProductVariant::class))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('delete', $variant))->toBeFalse();
});

test('38. Staff cannot mutate Attribute definitions', function () {
    $staff = User::factory()->create(['can_access_admin_panel' => true]);
    $staff->assignRole('Staff');
    $this->actingAs($staff);

    $attribute = Attribute::factory()->create();

    expect(AttributeResource::canCreate())->toBeFalse()
        ->and(AttributeResource::canEdit($attribute))->toBeFalse()
        ->and(AttributeResource::canDelete($attribute))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('create', Attribute::class))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('update', $attribute))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('delete', $attribute))->toBeFalse();
});

test('39. Manager can mutate allowed configuration', function () {
    $manager = User::factory()->create(['can_access_admin_panel' => true]);
    $manager->assignRole('Manager');
    $this->actingAs($manager);

    $attribute = Attribute::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    expect(Gate::forUser($manager)->allows('create', Attribute::class))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $attribute))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $product))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('create', ProductVariant::class))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('update', $variant))->toBeTrue();
});

test('40. Admin can mutate everything allowed by existing Gate::before', function () {
    $admin = User::factory()->create(['can_access_admin_panel' => true]);
    $admin->assignRole('Admin');
    $this->actingAs($admin);

    $attribute = Attribute::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'unit_id' => $unit->id]);

    expect(Gate::forUser($admin)->allows('create', Attribute::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $attribute))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $attribute))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Product::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $product))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $product))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', ProductVariant::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $variant))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $variant))->toBeTrue();
});
