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
use App\Models\ShippingMethod;
use App\Payments\NagadSigner;
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
        ->and($payment->order->status)->toBe(OrderStatus::Confirmed)
        ->and(Payment::query()->where('status', PaymentTransactionStatus::Completed)->count())->toBe(1)
        ->and((int) $payment->order->items()->first()->productVariant->inventory->fresh()->quantity)->toBe(9);
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

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Failed)
        ->and($payment->order->fresh()->status)->toBe(OrderStatus::Cancelled);
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

test('checkout charges the server shipping rate for the city', function () {
    $this->post('/checkout', gatewayCheckoutPayload([
        'shipping_city' => 'Chattogram',
        'shipping_amount' => 1,
    ]))->assertRedirect();

    expect((float) Order::query()->first()->shipping_amount)->toBe(120.0);
});

test('checkout uses the selected shipping method rate', function () {
    $method = ShippingMethod::query()->create([
        'name' => 'Outside Dhaka (All Bangladesh)',
        'code' => 'outside_dhaka_std',
        'charge' => 130,
        'is_active' => true,
    ]);

    $this->post('/checkout', gatewayCheckoutPayload([
        'shipping_city' => 'Chattogram',
        'shipping_amount' => 1,
        'shipping_method_id' => $method->id,
    ]))->assertRedirect();

    expect((float) Order::query()->first()->shipping_amount)->toBe(130.0);
});

test('a captured online payment is confirmed when the provider is asked again', function () {
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
            'element' => [[
                'status' => 'VALID',
                'tran_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency_type' => 'BDT',
                'val_id' => 'VALQ',
                'bank_tran_id' => 'BANKQ',
            ]],
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']));
    $order = Order::query()->first();
    $order->forceFill(['created_at' => now()->subHours(3)])->save();

    $this->artisan('payments:release-abandoned')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payments()->first()->status)->toBe(PaymentTransactionStatus::Completed);
});

test('a lookalike gateway host does not receive the customer', function () {
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
            'GatewayPageURL' => 'https://evilsslcommerz.com/phish',
        ]),
    ]);

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']))
        ->assertSessionHasErrors('payment');

    expect(Order::query()->first()->status)->toBe(OrderStatus::Cancelled);
});

test('a captured card payment is refunded through the gateway before the ledger changes', function () {
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

        if (str_contains($request->url(), 'merchantTransIDvalidationAPI')) {
            return Http::response(['status' => 'success', 'refund_ref_id' => 'RF1']);
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'status' => 'VALID',
            'tran_id' => $payment->transaction_id,
            'amount' => $payment->amount,
            'currency_type' => 'BDT',
            'val_id' => 'VAL1',
            'bank_tran_id' => 'BANK1',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'sslcommerz']));
    $payment = Payment::query()->first();
    $this->get(route('payments.return', [
        'gateway' => 'sslcommerz',
        'outcome' => 'success',
        'tran_id' => $payment->transaction_id,
        'val_id' => 'VAL1',
    ]));

    $payment->fresh()->refund('Customer return');

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Refunded);
});

test('an unpaid online order is cancelled after the payment window', function () {
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
    $online = Order::query()->first();
    $online->forceFill(['created_at' => now()->subHours(3)])->save();

    $this->post('/checkout', gatewayCheckoutPayload([
        'customer_phone' => '01822222222',
        'payment_method' => 'cod',
    ]));
    $cod = Order::query()->where('payment_method', 'cod')->first();
    $cod->forceFill(['created_at' => now()->subHours(3)])->save();

    $this->artisan('payments:release-abandoned')->assertSuccessful();

    expect($online->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($cod->fresh()->status)->toBe(OrderStatus::Pending);
});

test('bkash checkout is confirmed only after bkash reports the payment complete', function () {
    PaymentGatewaySettings::save([
        'gateways' => [
            'bkash_checkout' => [
                'enabled' => true,
                'app_key' => 'app-key',
                'app_secret' => 'app-secret',
                'username' => 'sandbox-user',
                'password' => 'sandbox-pass',
                'sandbox' => true,
            ],
        ],
    ]);

    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'token/grant')) {
            return Http::response(['id_token' => 'token-1']);
        }

        if (str_contains($url, '/create')) {
            return Http::response([
                'statusCode' => '0000',
                'paymentID' => 'PAYID1',
                'bkashURL' => 'https://tokenized.sandbox.bka.sh/checkout?paymentId=PAYID1',
            ]);
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'statusCode' => '0000',
            'transactionStatus' => 'Completed',
            'currency' => 'BDT',
            'amount' => $payment->amount,
            'merchantInvoiceNumber' => $payment->payment_number,
            'paymentID' => 'PAYID1',
            'trxID' => 'TRX1',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'bkash_checkout']))
        ->assertRedirect('https://tokenized.sandbox.bka.sh/checkout?paymentId=PAYID1');

    $payment = Payment::query()->first();

    $this->get(route('payments.return', [
        'gateway' => 'bkash_checkout',
        'outcome' => 'success',
        'paymentID' => 'PAYID1',
        'status' => 'success',
    ]))->assertRedirect(route('checkout.success', ['order_number' => $payment->order->order_number]));

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Completed)
        ->and($payment->order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

test('nagad checkout is confirmed only after nagad verifies the payment', function () {
    [$merchantPrivate, $merchantPublic] = nagadKeyPair();
    [$nagadPrivate, $nagadPublic] = nagadKeyPair();

    PaymentGatewaySettings::save([
        'gateways' => [
            'nagad_checkout' => [
                'enabled' => true,
                'merchant_id' => 'merchant1',
                'merchant_number' => '01700000000',
                'public_key' => $nagadPublic,
                'private_key' => $merchantPrivate,
                'sandbox' => true,
            ],
        ],
    ]);

    $signed = function (array $payload) use ($merchantPublic, $nagadPrivate): array {
        $plain = json_encode($payload, JSON_THROW_ON_ERROR);

        return [
            'sensitiveData' => NagadSigner::encrypt($plain, $merchantPublic),
            'signature' => NagadSigner::sign($plain, $nagadPrivate),
        ];
    };

    Http::fake(function ($request) use ($signed) {
        $url = $request->url();

        if (str_contains($url, 'initialize')) {
            return Http::response($signed([
                'paymentReferenceId' => 'REF1',
                'challenge' => 'challenge-1',
            ]));
        }

        if (str_contains($url, 'complete')) {
            return Http::response($signed([
                'callBackUrl' => 'http://sandbox.mynagad.com/checkout?ref=1',
            ]));
        }

        $payment = Payment::query()->latest('id')->first();

        return Http::response([
            'status' => 'Success',
            'orderId' => $payment->transaction_id,
            'amount' => $payment->amount,
            'issuerPaymentRefNo' => 'ISS1',
        ]);
    });

    $this->post('/checkout', gatewayCheckoutPayload(['payment_method' => 'nagad_checkout']))
        ->assertRedirect('http://sandbox.mynagad.com/checkout?ref=1');

    $payment = Payment::query()->first();

    $this->get(route('payments.return', [
        'gateway' => 'nagad_checkout',
        'outcome' => 'success',
        'order_id' => $payment->transaction_id,
        'payment_ref_id' => 'REF1',
        'status' => 'Success',
    ]))->assertRedirect(route('checkout.success', ['order_number' => $payment->order->order_number]));

    expect($payment->fresh()->status)->toBe(PaymentTransactionStatus::Completed)
        ->and($payment->order->fresh()->status)->toBe(OrderStatus::Confirmed);
});

function nagadKeyPair(): array
{
    $config = [
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    foreach ([getenv('OPENSSL_CONF') ?: '', 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf', '/etc/ssl/openssl.cnf'] as $candidate) {
        if ($candidate !== '' && is_file($candidate)) {
            $config['config'] = $candidate;
            break;
        }
    }

    $resource = openssl_pkey_new($config);
    openssl_pkey_export($resource, $private, null, $config);
    $public = openssl_pkey_get_details($resource)['key'];

    return [$private, $public];
}
