<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAuthController extends Controller
{
    /**
     * Display customer login form.
     */
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.account');
        }

        return Inertia::render('Auth/CustomerLogin');
    }

    /**
     * Authenticate customer using phone/email and password.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($credentials['login']);
        $remember = $request->boolean('remember');

        // Check whether login input is email or phone number
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $customerQuery = Customer::query()->where('is_active', true);
        if ($isEmail) {
            $customerQuery->where('email', $loginInput);
        } else {
            // Match phone (e.g. 01711111111 or +8801711111111)
            $cleaned = preg_replace('/[^\d]/', '', $loginInput);
            $customerQuery->where(function ($q) use ($loginInput, $cleaned) {
                $q->where('phone', $loginInput)
                    ->orWhere('phone', $cleaned);
                if (str_starts_with($cleaned, '8801')) {
                    $q->orWhere('phone', substr($cleaned, 2));
                }
            });
        }

        $customer = $customerQuery->first();

        if (! $customer || ! $customer->password || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'login' => 'ফোন নম্বর/ইমেইল অথবা পাসওয়ার্ডটি সঠিক নয়।',
            ]);
        }

        Auth::guard('customer')->login($customer, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('success', 'স্বাগতম, '.$customer->name.'! আপনি সফলভাবে লগইন করেছেন।');
    }

    /**
     * Display customer registration form.
     */
    public function showRegister(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.account');
        }

        return Inertia::render('Auth/CustomerRegister');
    }

    /**
     * Register a new customer account.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $phone = trim($validated['phone']);

        // Check if a customer already exists with this phone
        $existing = Customer::where('phone', $phone)->first();
        if ($existing) {
            if ($existing->password !== null) {
                throw ValidationException::withMessages([
                    'phone' => 'এই ফোন নম্বর দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট রয়েছে। দয়া করে লগইন করুন।',
                ]);
            }

            // Upgrade guest customer record to full account
            $existing->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? $existing->email,
                'password' => $validated['password'],
            ]);
            $customer = $existing;
        } else {
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $phone,
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'is_active' => true,
            ]);
        }

        // Welcome SMS
        try {
            app(SmsService::class)->sendCustomerRegistrationSms($customer);
        } catch (\Throwable $e) {
            // Silently continue
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.account')->with('success', 'আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! স্বাগতম।');
    }

    /**
     * Log out authenticated customer.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'আপনি সফলভাবে লগআউট করেছেন।');
    }

    /**
     * Display customer account dashboard with order history.
     */
    public function showAccount(): Response|RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $orders = Order::query()
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->orWhere('customer_phone', $customer->phone);
            })
            ->with([
                'items',
                'statusHistories' => function ($q) {
                    $q->latest();
                },
            ])
            ->latest()
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'payment_status' => $order->payment_status->value,
                    'payment_method' => $order->payment_method->value,
                    'grand_total' => (float) $order->grand_total,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_amount' => (float) $order->shipping_amount,
                    'created_at' => $order->created_at->format('d M Y, h:i A'),
                    'shipping_address' => $order->shipping_address_line.', '.$order->shipping_city,
                    'items_count' => $order->items->sum('quantity'),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'name' => $item->variant_name ?? $item->product_name,
                            'quantity' => $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'line_total' => (float) $item->line_total,
                        ];
                    }),
                ];
            });

        return Inertia::render('Customer/Account', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'member_since' => $customer->created_at->format('d M Y'),
            ],
            'orders' => $orders,
            'stats' => [
                'total_orders' => $orders->count(),
                'total_spent' => $orders->sum('grand_total'),
            ],
        ]);
    }

    /**
     * Update customer profile info.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => ['nullable', Password::min(6), 'confirmed'],
        ]);

        if (! empty($validated['new_password'])) {
            if (! Hash::check($validated['current_password'], $customer->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'বর্তমান পাসওয়ার্ডটি সঠিক নয়।',
                ]);
            }
            $customer->password = $validated['new_password'];
        }

        $customer->name = $validated['name'];
        $customer->email = $validated['email'] ?? $customer->email;
        $customer->save();

        return back()->with('success', 'প্রোফাইল তথ্য সফলভাবে আপডেট হয়েছে!');
    }
}
