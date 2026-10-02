<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');
    $this->app['config']->set('database.connections.mysql.database', 'ecommerce_choco_test');

    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

afterEach(function () {
    Schema::disableForeignKeyConstraints();
    DB::table('order_items')->truncate();
    DB::table('orders')->truncate();
    DB::table('inventory_movements')->truncate();
    DB::table('inventories')->truncate();
    ProductVariant::truncate();
    Product::truncate();
    Brand::truncate();
    Category::truncate();
    Unit::truncate();
    DB::table('category_product')->truncate();
    Schema::enableForeignKeyConstraints();
});

// Helper to create valid ProductVariant for tests
function createOrderTestVariant(array $attributes = []): ProductVariant
{
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'name' => 'Artisan Dark Chocolate',
        'brand_id' => $brand->id,
    ]);
    $product->categories()->attach($category->id);

    $sellingPrice = $attributes['selling_price'] ?? 150;
    $compareAtPrice = $attributes['compare_at_price'] ?? ($sellingPrice + 50);

    return ProductVariant::factory()->create(array_merge([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'name' => '100g Classic Bar',
        'sku' => 'VAR-TEST-'.uniqid(),
        'cost_price' => 50,
        'selling_price' => $sellingPrice,
        'compare_at_price' => $compareAtPrice,
        'is_active' => true,
        'is_default' => true,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| CAT-9A: ORDER DATABASE FOUNDATION TESTS
|--------------------------------------------------------------------------
*/

test('A. Order can be created with valid attributes', function () {
    $order = Order::create([
        'order_number' => 'ORD-100001',
        'customer_name' => 'Rahim Ahmed',
        'customer_phone' => '01711000001',
        'customer_email' => 'rahim@example.com',
        'customer_note' => 'Please deliver in the evening',
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::Cod,
        'currency' => 'BDT',
        'subtotal' => 500.00,
        'discount_amount' => 50.00,
        'shipping_amount' => 60.00,
        'grand_total' => 510.00,
        'shipping_address_line' => 'House 12, Road 5, Block C',
        'shipping_area' => 'Banani',
        'shipping_city' => 'Dhaka',
        'shipping_postcode' => '1213',
        'shipping_country' => 'Bangladesh',
        'billing_same_as_shipping' => true,
        'placed_at' => now(),
    ]);

    expect($order->exists)->toBeTrue()
        ->and($order->id)->toBeGreaterThan(0)
        ->and($order->order_number)->toBe('ORD-100001')
        ->and($order->customer_name)->toBe('Rahim Ahmed')
        ->and($order->currency)->toBe('BDT')
        ->and($order->subtotal)->toBe('500.00')
        ->and($order->grand_total)->toBe('510.00');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'order_number' => 'ORD-100001',
        'customer_phone' => '01711000001',
    ]);
});

test('B. Guest order works with user_id = null', function () {
    $order = Order::factory()->create([
        'user_id' => null,
    ]);

    expect($order->user_id)->toBeNull()
        ->and($order->user)->toBeNull();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => null,
    ]);
});

test('C. Registered order can reference a User', function () {
    $email = 'order-user-'.uniqid().'@example.com';
    $user = User::factory()->create([
        'name' => 'Karim User',
        'email' => $email,
    ]);

    $order = Order::factory()->forUser($user)->create();

    expect($order->user_id)->toBe($user->id)
        ->and($order->user)->toBeInstanceOf(User::class)
        ->and($order->user->id)->toBe($user->id)
        ->and($order->user->email)->toBe($email);
});

test('D. Order number is unique at database level', function () {
    Order::factory()->create(['order_number' => 'ORD-UNIQUE-999']);

    expect(function () {
        Order::factory()->create(['order_number' => 'ORD-UNIQUE-999']);
    })->toThrow(QueryException::class);
});

test('E. Order has many items', function () {
    $order = Order::factory()->create();
    $variant1 = createOrderTestVariant(['sku' => 'SKU-ITEM-1']);
    $variant2 = createOrderTestVariant(['sku' => 'SKU-ITEM-2']);

    $item1 = OrderItem::factory()->forVariant($variant1, 2)->create(['order_id' => $order->id]);
    $item2 = OrderItem::factory()->forVariant($variant2, 1)->create(['order_id' => $order->id]);

    expect($order->items)->toHaveCount(2)
        ->and($order->orderItems)->toHaveCount(2)
        ->and($order->items->pluck('id')->all())->toEqualCanonicalizing([$item1->id, $item2->id]);
});

test('F. OrderItem belongs to Order', function () {
    $order = Order::factory()->create();
    $variant = createOrderTestVariant();

    $item = OrderItem::factory()->forVariant($variant)->create(['order_id' => $order->id]);

    expect($item->order)->toBeInstanceOf(Order::class)
        ->and($item->order->id)->toBe($order->id);
});

test('G. OrderItem belongs to ProductVariant', function () {
    $order = Order::factory()->create();
    $variant = createOrderTestVariant();

    $item = OrderItem::factory()->forVariant($variant)->create(['order_id' => $order->id]);

    expect($item->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($item->productVariant->id)->toBe($variant->id);
});

test('H. Snapshot fields exist and are stored independently', function () {
    $order = Order::factory()->create();
    $variant = createOrderTestVariant([
        'name' => '70% Dark Chocolate Bar',
        'sku' => 'CHOCO-70-DARK',
        'selling_price' => 250.00,
    ]);

    $item = OrderItem::create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Artisan Dark Chocolate',
        'variant_name' => '70% Dark Chocolate Bar',
        'sku' => 'CHOCO-70-DARK',
        'unit_price' => 250.00,
        'quantity' => 3,
        'discount_amount' => 50.00,
        'line_total' => 700.00,
    ]);

    expect($item->product_name)->toBe('Artisan Dark Chocolate')
        ->and($item->variant_name)->toBe('70% Dark Chocolate Bar')
        ->and($item->sku)->toBe('CHOCO-70-DARK')
        ->and($item->unit_price)->toBe('250.00')
        ->and($item->quantity)->toBe(3)
        ->and($item->discount_amount)->toBe('50.00')
        ->and($item->line_total)->toBe('700.00');

    $this->assertDatabaseHas('order_items', [
        'id' => $item->id,
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => 'CHOCO-70-DARK',
    ]);
});

test('I. Enum casts work correctly on Order model', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::Cod,
    ]);

    expect($order->status)->toBe(OrderStatus::Processing)
        ->and($order->status->value)->toBe('processing')
        ->and($order->status->label())->toBe('Processing')
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->payment_status->value)->toBe('paid')
        ->and($order->payment_status->label())->toBe('Paid')
        ->and($order->payment_method)->toBe(PaymentMethod::Cod)
        ->and($order->payment_method->value)->toBe('cod')
        ->and($order->payment_method->label())->toBe('Cash on Delivery');

    // Test enum string assignment and casting
    $order->status = 'shivered'; // invalid enum value will throw ValueError
})->throws(ValueError::class);

test('J. Monetary fields use expected decimal representation and precision', function () {
    $order = Order::factory()->create([
        'subtotal' => 1234.56,
        'discount_amount' => 100.25,
        'shipping_amount' => 60.50,
        'grand_total' => 1194.81,
    ]);

    expect($order->subtotal)->toBe('1234.56')
        ->and($order->discount_amount)->toBe('100.25')
        ->and($order->shipping_amount)->toBe('60.50')
        ->and($order->grand_total)->toBe('1194.81');

    $variant = createOrderTestVariant();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'unit_price' => 199.99,
        'quantity' => 2,
        'discount_amount' => 15.50,
        'line_total' => 384.48,
    ]);

    expect($item->unit_price)->toBe('199.99')
        ->and($item->discount_amount)->toBe('15.50')
        ->and($item->line_total)->toBe('384.48');
});

test('K. Foreign key constraints enforce referential integrity and prevent accidental cascade delete', function () {
    // 1. Invalid user_id fails
    expect(function () {
        Order::factory()->create(['user_id' => 999999]);
    })->toThrow(QueryException::class);

    // 2. Invalid product_variant_id on order_items fails
    $order = Order::factory()->create();
    expect(function () use ($order) {
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => 999999,
        ]);
    })->toThrow(QueryException::class);

    // 3. Deleting a user sets user_id to null on the order, keeping order intact
    $user = User::factory()->create();
    $userOrder = Order::factory()->forUser($user)->create();
    $orderId = $userOrder->id;

    $user->delete();
    $userOrder->refresh();

    expect($userOrder->user_id)->toBeNull()
        ->and(Order::find($orderId))->not->toBeNull();

    // 4. Deleting an Order cascades and deletes its order items
    $variant = createOrderTestVariant();
    $item = OrderItem::factory()->forVariant($variant)->create(['order_id' => $userOrder->id]);
    $itemId = $item->id;

    $userOrder->delete();
    expect(OrderItem::find($itemId))->toBeNull();

    // 5. Restrict on delete: hard deleting a ProductVariant referenced by an OrderItem is restricted by database FK
    $order2 = Order::factory()->create();
    $variant2 = createOrderTestVariant();
    $item2 = OrderItem::factory()->forVariant($variant2)->create(['order_id' => $order2->id]);

    expect(function () use ($variant2) {
        DB::table('product_variants')->where('id', $variant2->id)->delete();
    })->toThrow(QueryException::class);
});

test('L. cancelled_by can be null and handles user deletion gracefully', function () {
    $order = Order::factory()->create([
        'placed_at' => now(),
        'cancelled_at' => null,
        'cancelled_by' => null,
        'cancellation_reason' => null,
    ]);

    expect($order->cancelled_by)->toBeNull()
        ->and($order->cancelledBy)->toBeNull();

    // Now cancel by admin
    $admin = User::factory()->create();
    $order->update([
        'status' => OrderStatus::Cancelled,
        'cancelled_at' => now(),
        'cancelled_by' => $admin->id,
        'cancellation_reason' => 'Duplicate order placed by customer',
    ]);
    $order->refresh();

    expect($order->cancelledBy)->toBeInstanceOf(User::class)
        ->and($order->cancelledBy->id)->toBe($admin->id)
        ->and($order->cancellation_reason)->toBe('Duplicate order placed by customer');

    // Deleting admin nulls cancelled_by FK, preserving cancellation record
    $admin->delete();
    $order->refresh();

    expect($order->cancelled_by)->toBeNull()
        ->and($order->cancellation_reason)->toBe('Duplicate order placed by customer');
});

test('M. Address snapshot fields are stored correctly', function () {
    $order = Order::factory()->withDifferentBilling()->create([
        'shipping_address_line' => 'Flat 4A, Plot 18, Road 2',
        'shipping_area' => 'Gulshan-1',
        'shipping_city' => 'Dhaka',
        'shipping_postcode' => '1212',
        'shipping_country' => 'Bangladesh',
        'billing_same_as_shipping' => false,
        'billing_address_line' => 'House 7, Road 14',
        'billing_area' => 'Nasirabad',
        'billing_city' => 'Chittagong',
        'billing_postcode' => '4000',
        'billing_country' => 'Bangladesh',
    ]);

    expect($order->shipping_address_line)->toBe('Flat 4A, Plot 18, Road 2')
        ->and($order->shipping_area)->toBe('Gulshan-1')
        ->and($order->shipping_city)->toBe('Dhaka')
        ->and($order->shipping_postcode)->toBe('1212')
        ->and($order->shipping_country)->toBe('Bangladesh')
        ->and($order->billing_same_as_shipping)->toBeFalse()
        ->and($order->billing_address_line)->toBe('House 7, Road 14')
        ->and($order->billing_area)->toBe('Nasirabad')
        ->and($order->billing_city)->toBe('Chittagong')
        ->and($order->billing_postcode)->toBe('4000')
        ->and($order->billing_country)->toBe('Bangladesh');
});

test('N. Order factory produces valid instances and custom states work', function () {
    // Default factory
    $order = Order::factory()->create();
    expect($order->order_number)->toStartWith('ORD-')
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->payment_method)->toBe(PaymentMethod::Cod)
        ->and($order->currency)->toBe('BDT')
        ->and((float) $order->grand_total)->toBeGreaterThan(0);

    // Cancelled state
    $admin = User::factory()->create();
    $cancelledOrder = Order::factory()->cancelled($admin, 'Out of stock')->create();
    expect($cancelledOrder->status)->toBe(OrderStatus::Cancelled)
        ->and($cancelledOrder->cancelled_by)->toBe($admin->id)
        ->and($cancelledOrder->cancellation_reason)->toBe('Out of stock')
        ->and($cancelledOrder->cancelled_at)->not->toBeNull();
});

test('O. OrderItem factory produces valid instances and forVariant state works', function () {
    $variant = createOrderTestVariant([
        'name' => '250g Gift Box',
        'sku' => 'GIFT-BOX-250',
        'selling_price' => 850.00,
    ]);

    $item = OrderItem::factory()->forVariant($variant, 3)->create();

    expect($item->product_variant_id)->toBe($variant->id)
        ->and($item->product_name)->toBe('Artisan Dark Chocolate')
        ->and($item->variant_name)->toBe('250g Gift Box')
        ->and($item->sku)->toBe('GIFT-BOX-250')
        ->and($item->unit_price)->toBe('850.00')
        ->and($item->quantity)->toBe(3)
        ->and($item->discount_amount)->toBe('0.00')
        ->and($item->line_total)->toBe('2550.00');
});

test('Historical snapshot values do not automatically depend on current ProductVariant data', function () {
    $variant = createOrderTestVariant([
        'name' => 'Original 50g Bar',
        'sku' => 'SKU-ORIGINAL-50',
        'selling_price' => 100.00,
    ]);

    $order = Order::factory()->create();
    $item = OrderItem::factory()->forVariant($variant, 2)->create([
        'order_id' => $order->id,
    ]);

    expect($item->product_name)->toBe('Artisan Dark Chocolate')
        ->and($item->variant_name)->toBe('Original 50g Bar')
        ->and($item->sku)->toBe('SKU-ORIGINAL-50')
        ->and($item->unit_price)->toBe('100.00')
        ->and($item->line_total)->toBe('200.00');

    // Mutate the original Product and Variant
    $variant->product->update(['name' => 'Completely Renamed Product']);
    $variant->updateQuietly([
        'name' => 'Rebranded 100g Bar',
        'sku' => 'SKU-MUTATED-99',
        'selling_price' => 300.00,
    ]);

    // Reload OrderItem from fresh database state
    $reloadedItem = OrderItem::find($item->id);

    // Snapshot values MUST remain unchanged
    expect($reloadedItem->product_name)->toBe('Artisan Dark Chocolate')
        ->and($reloadedItem->variant_name)->toBe('Original 50g Bar')
        ->and($reloadedItem->sku)->toBe('SKU-ORIGINAL-50')
        ->and($reloadedItem->unit_price)->toBe('100.00')
        ->and($reloadedItem->line_total)->toBe('200.00');

    // Meanwhile the current variant reflects the new data
    expect($reloadedItem->productVariant->name)->toBe('Rebranded 100g Bar')
        ->and($reloadedItem->productVariant->sku)->toBe('SKU-MUTATED-99')
        ->and($reloadedItem->productVariant->selling_price)->toBe('300.00')
        ->and($reloadedItem->productVariant->product->name)->toBe('Completely Renamed Product');
});
