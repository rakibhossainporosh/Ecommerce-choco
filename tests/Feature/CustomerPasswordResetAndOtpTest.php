<?php

use App\Mail\CustomerPasswordResetMail;
use App\Models\Customer;
use App\Support\CustomerOtp;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

test('customer can request password reset and set a new password with code', function () {
    $customer = Customer::factory()->create([
        'phone' => '01710000001',
        'email' => 'reset@example.com',
        'password' => 'old-pass-123',
        'is_active' => true,
    ]);

    $this->post('/forgot-password', ['login' => '01710000001'])
        ->assertRedirect(route('customer.password.reset'));

    Mail::assertSent(CustomerPasswordResetMail::class);

    $code = '654321';
    Cache::put(CustomerOtp::codeKey(CustomerOtp::PURPOSE_RESET, $customer->id), Hash::make($code), 600);
    session(['customer_password_reset_id' => $customer->id]);

    $this->post('/reset-password', [
        'code' => $code,
        'password' => 'new-pass-123',
        'password_confirmation' => 'new-pass-123',
    ])->assertRedirect('/account');

    $this->assertTrue(Auth::guard('customer')->check());
    $this->assertTrue(Hash::check('new-pass-123', $customer->fresh()->password));
});

test('customer can login with otp after requesting a code', function () {
    $customer = Customer::factory()->create([
        'phone' => '01710000002',
        'email' => 'otp@example.com',
        'password' => 'secret-pass',
        'is_active' => true,
    ]);

    $this->post('/login/otp', ['login' => '01710000002'])->assertRedirect();

    $code = '112233';
    Cache::put(CustomerOtp::codeKey(CustomerOtp::PURPOSE_LOGIN, $customer->id), Hash::make($code), 600);
    session(['customer_otp_login_id' => $customer->id]);

    $this->post('/login/otp/verify', [
        'code' => $code,
        'remember' => true,
    ])->assertRedirect('/account');

    $this->assertTrue(Auth::guard('customer')->check());
    $this->assertEquals($customer->id, Auth::guard('customer')->id());
});

test('invalid otp code is rejected', function () {
    $customer = Customer::factory()->create([
        'phone' => '01710000003',
        'password' => 'secret-pass',
        'is_active' => true,
    ]);

    Cache::put(CustomerOtp::codeKey(CustomerOtp::PURPOSE_LOGIN, $customer->id), Hash::make('999999'), 600);
    session(['customer_otp_login_id' => $customer->id]);

    $this->post('/login/otp/verify', ['code' => '111111'])
        ->assertSessionHasErrors('code');

    $this->assertFalse(Auth::guard('customer')->check());
});
