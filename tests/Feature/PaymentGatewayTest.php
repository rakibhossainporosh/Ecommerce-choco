<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Setting;
use App\Support\PaymentGatewaySettings;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StorefrontSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    Payment::query()->delete();
    Review::query()->delete();
    DB::table('category_product')->delete();
    Inventory::query()->delete();
    ProductVariant::query()->delete();
    Product::query()->delete();
    Category::query()->delete();
    Order::query()->delete();
    Customer::query()->delete();
    Schema::enableForeignKeyConstraints();

    $this->seed(RolePermissionSeeder::class);
    $this->seed(StorefrontSeeder::class);
});

function gatewayCheckoutPayload(array $overrides = []): array
{
    $product = Product::where('slug', 'cotton-shirt')->first();
    $variant = $product->defaultVariant;
    $variant->inventory->update(['quantity' => 10]);

    return array_merge([
        'customer_name' => 'আরিফ হোসেন',
        'customer_phone' => '01811111111',
        'customer_email' => 'arif@example.com',
        'shipping_address_line' => 'মিরপুর ১০, ঢাকা',
        'shipping_city' => 'Dhaka',
        'shipping_amount' => 60,
        'payment_method' => 'cod',
        'cart_items' => [[
            'id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]],
    ], $overrides);
}

test('checkout offers cash on delivery until another method is ready', function () {
    $this->get('/checkout')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.key', 'cod')
            ->where('payment_methods.0.mode', 'cod'));
});

test('cash on delivery checkout does not create a payment row', function () {
    $this->post('/checkout', gatewayCheckoutPayload())
        ->assertRedirect();

    expect(Order::query()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(0)
        ->and(Order::query()->first()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('an unavailable payment method does not place an order', function () {
    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'bkash']))
        ->assertSessionHasErrors('payment_method');

    expect(Order::query()->count())->toBe(0);
});

test('manual payment shows the account and keeps the payment pending', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'bkash' => [
                'enabled' => true,
                'account' => '01711111111',
                'instructions' => 'Send the total, then keep the TrxID.',
            ],
        ],
    ]);

    $this->post('/checkout', gatewayCheckoutPayload([
        'payment_method' => 'bkash',
        'transaction_reference' => 'TRX123456',
    ]))->assertRedirect();

    $order = Order::query()->first();
    $payment = Payment::query()->first();

    expect($payment->status)->toBe(PaymentTransactionStatus::Pending)
        ->and($payment->transaction_id)->toBe('TRX123456')
        ->and($payment->account_number)->toBe('01711111111')
        ->and($order->payment_status)->toBe(PaymentStatus::Pending);

    $this->get(route('checkout.success', ['order_number' => $order->order_number]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment.mode', 'manual')
            ->where('payment.account', '01711111111')
            ->where('payment.reference', 'TRX123456'));
});

test('online checkout requires an email and stores the gateway password encrypted', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'sslcommerz' => [
                'enabled' => true,
                'store_id' => 'nobo_test',
                'store_password' => 'store-secret',
                'sandbox' => true,
            ],
        ],
    ]);

    $stored = Setting::query()->where('key', 'payment_gw_sslcommerz_store_password')->value('value');

    expect($stored)->not->toBe('store-secret')
        ->and(PaymentGatewaySettings::secret('sslcommerz', 'store_password'))->toBe('store-secret');

    $this->post('/checkout', gatewayCheckoutPayload([
        'payment_method' => 'sslcommerz',
        'customer_email' => null,
    ]))->assertSessionHasErrors('customer_email');

    expect(Order::query()->count())->toBe(0);
});

test('sslcommerz checkout redirects and a validated return marks the order paid once', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'sslcommerz' => [
                'enabled' => true,
                'store_id' => 'nobo_test',
                'store_password' => 'store-secret',
                'sandbox' => true,
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'gwprocess')) {
            return Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php?session=1',
                'sessionkey' => 'session-1',
            ]);
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'status' => 'VALID',
            'tran_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency_type' => 'BDT',
            'val_id' => 'VAL1',
            'card_type' => 'VISA',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']))
        ->assertRedirect('https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php?session=1');

    $payment = Payment::query()->first();

    expect($payment->status)->toBe(PaymentTransactionStatus::Pending)
        ->and($payment->payment_method)->toBe('sslcommerz');

    $this->get(route('payments.return', [
        'gateway' => 'sslcommerz',
        'outcome' => 'success',
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL1',
    ]))->assertRedirect(route('checkout.success', ['order_number' => $payment->order->order_number]));

    $this->get(route('payments.return', [
        'gateway' => 'sslcommerz',
        'outcome' => 'success',
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL1',
    ]))->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Completed)
        ->and($payment->order->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($payment->order->status)->toBe(OrderStatus::Pending)
        ->and(Payment::query()->where('status', PaymentTransactionStatus::Completed)->count())->toBe(1);
});

test('a mismatched gateway amount does not mark the payment paid', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'sslcommerz' => [
                'enabled' => true,
                'store_id' => 'nobo_test',
                'store_password' => 'store-secret',
                'sandbox' => true,
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'gwprocess')) {
            return Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php?session=1',
                'sessionkey' => 'session-1',
            ]);
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'status' => 'VALID',
            'tran_id' => $payment->transaction_id,
            'amount' => '1.00',
            'currency_type' => 'BDT',
            'val_id' => 'VAL1',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']));

    $payment = Payment::query()->first();

    $this->get(route('payments.return', [
        'gateway' => 'sslcommerz',
        'outcome' => 'success',
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL1',
    ]))->assertRedirect(route('checkout.index'));

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Pending)
        ->and($payment->order->fresh()->payment_status)->not->toBe(PaymentStatus::Paid);
});

test('cancelling the gateway return fails the pending payment', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'sslcommerz' => [
                'enabled' => true,
                'store_id' => 'nobo_test',
                'store_password' => 'store-secret',
                'sandbox' => true,
            ],
        ],
    ]);

    Http::fake([
        'sandbox.sslcommerz.com/*' => Http::response([
            'status' => 'SUCCESS',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php?session=1',
            'sessionkey' => 'session-1',
        ]),
    ]);

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']));
    $payment = Payment::query()->first();

    $this->get(route('payments.return', [
        'gateway' => 'sslcommerz',
        'outcome' => 'cancel',
        'tran_id' => $payment->transaction_id,
    ]))->assertRedirect(route('checkout.index'));

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Failed);
});

test('the payment notification confirms the same transaction', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'sslcommerz' => [
                'enabled' => true,
                'store_id' => 'nobo_test',
                'store_password' => 'store-secret',
                'sandbox' => true,
            ],
        ],
    ]);

    Http::fake(function ($request) {
        if (str_contains($request->url(), 'gwprocess')) {
            return Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gwprocess/v4/bankgw/indexhtml.php?session=1',
                'sessionkey' => 'session-1',
            ]);
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'status' => 'VALIDATED',
            'tran_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency_type' => 'BDT',
            'val_id' => 'VALIPN',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']));
    $payment = Payment::query()->first();

    $this->post(route('payments.ipn', ['gateway' => 'sslcommerz']), [
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VALIPN',
    ])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Completed);
});

test('a gateway that is not a redirect endpoint is not accepted', function () {
    $this->post('/payments/cod/ipn')->assertNotFound();
});
