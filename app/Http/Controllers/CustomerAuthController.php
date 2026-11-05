<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ReturnRequestStatus;
use App\Mail\CustomerPasswordResetMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Services\SmsService;
use App\Support\CustomerOtp;
use App\Support\MetaPixel;
use App\Support\ReturnItemValidator;
use App\Support\ReturnPolicySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
                'login' => __('storefront.auth.invalid_credentials'),
            ]);
        }

        Auth::guard('customer')->login($customer, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('success', __('storefront.auth.welcome', ['name' => $customer->name]));
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
                    'phone' => __('storefront.auth.phone_taken'),
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
        MetaPixel::rememberRegistration($customer->id);

        return redirect()->route('customer.account')->with('success', __('storefront.auth.registered'));
    }

    /**
     * Log out authenticated customer.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('storefront.auth.logged_out'));
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

        $ordersQuery = Order::query()
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->orWhere('customer_phone', $customer->phone);
            });

        $orders = (clone $ordersQuery)
            ->with([
                'items',
                'shipments',
                'statusHistories' => function ($q) {
                    $q->latest();
                },
                'returnRequests' => function ($q) {
                    $q->latest();
                },
            ])
            ->latest()
            ->get()
            ->map(function (Order $order) {
                $openReturn = $order->returnRequests
                    ->first(fn (ReturnRequest $returnRequest) => in_array($returnRequest->status, [
                        ReturnRequestStatus::Pending,
                        ReturnRequestStatus::Approved,
                        ReturnRequestStatus::Received,
                    ], true));

                $remainingByItem = ReturnItemValidator::remainingQuantities($order);
                $returnableItems = $order->items
                    ->filter(fn ($item): bool => (int) ($remainingByItem[$item->id] ?? 0) > 0)
                    ->map(function ($item) use ($remainingByItem) {
                        $remaining = (int) ($remainingByItem[$item->id] ?? 0);

                        return [
                            'id' => $item->id,
                            'name' => $item->variant_name ?? $item->product_name,
                            'quantity' => (int) $item->quantity,
                            'remaining_quantity' => $remaining,
                            'unit_price' => (float) $item->unit_price,
                            'line_total' => (float) $item->line_total,
                        ];
                    })
                    ->values();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'payment_status' => $order->payment_status->value,
                    'payment_method' => is_object($order->payment_method)
                        ? $order->payment_method->value
                        : (string) $order->payment_method,
                    'grand_total' => (float) $order->grand_total,
                    'subtotal' => (float) $order->subtotal,
                    'shipping_amount' => (float) $order->shipping_amount,
                    'created_at' => $order->created_at->format('d M Y, h:i A'),
                    'shipping_address' => $order->shipping_address_line.', '.$order->shipping_city,
                    'items_count' => $order->items->sum('quantity'),
                    'can_request_return' => $order->status === OrderStatus::Delivered
                        && $order->isWithinReturnWindow()
                        && $openReturn === null
                        && $returnableItems->isNotEmpty(),
                    'return_request' => $openReturn ? [
                        'id' => $openReturn->id,
                        'status' => $openReturn->status->value,
                        'refund_amount' => (float) $openReturn->refund_amount,
                        'reason' => $openReturn->reason,
                        'created_at' => $openReturn->created_at?->format('d M Y, h:i A'),
                    ] : null,
                    'items' => $returnableItems,
                    'shipments' => $order->shipments->map(fn ($shipment) => [
                        'shipment_number' => $shipment->shipment_number,
                        'status' => $shipment->status->value,
                        'tracking_code' => $shipment->tracking_code,
                        'tracking_url' => $shipment->getTrackingUrl(),
                    ])->values()->all(),
                ];
            });

        $returnRequests = ReturnRequest::query()
            ->where(function ($q) use ($customer, $ordersQuery) {
                $q->where('customer_id', $customer->id)
                    ->orWhereIn('order_id', (clone $ordersQuery)->select('id'));
            })
            ->with(['order:id,order_number', 'items'])
            ->latest()
            ->get()
            ->map(fn (ReturnRequest $returnRequest) => [
                'id' => $returnRequest->id,
                'rma_number' => $returnRequest->rma_number,
                'order_number' => $returnRequest->order?->order_number,
                'status' => $returnRequest->status->value,
                'status_label' => $returnRequest->status->label(),
                'reason' => $returnRequest->reason,
                'admin_note' => in_array($returnRequest->status, [
                    ReturnRequestStatus::Rejected,
                    ReturnRequestStatus::Cancelled,
                ], true)
                    ? $returnRequest->admin_note
                    : null,
                'refund_amount' => (float) $returnRequest->refund_amount,
                'items_count' => $returnRequest->items->sum('quantity'),
                'can_cancel' => $returnRequest->status === ReturnRequestStatus::Pending,
                'created_at' => $returnRequest->created_at?->format('d M Y, h:i A'),
            ]);

        $notifications = $customer->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => (string) data_get($notification->data, 'title', __('Notification')),
                'body' => (string) data_get($notification->data, 'body', ''),
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->format('d M Y, h:i A'),
            ]);

        $addresses = $customer->addresses()
            ->where('type', 'shipping')
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->map(fn ($address) => [
                'id' => $address->id,
                'name' => $address->name,
                'phone' => $address->phone,
                'address_line' => $address->address_line,
                'area' => $address->area,
                'city' => $address->city,
                'postcode' => $address->postcode,
                'country' => $address->country,
                'is_default' => (bool) $address->is_default,
                'formatted' => $address->formatted_address,
            ]);

        return Inertia::render('Customer/Account', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'member_since' => $customer->created_at->format('d M Y'),
            ],
            'orders' => $orders,
            'addresses' => $addresses,
            'return_requests' => $returnRequests,
            'notifications' => $notifications,
            'return_window_days' => ReturnPolicySettings::windowDays(),
            'stats' => [
                'total_orders' => $orders->count(),
                'total_spent' => $orders->sum('grand_total'),
            ],
        ]);
    }

    /**
     * Mark a customer database notification as read.
     */
    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $record = $customer->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return redirect()
            ->route('customer.account', ['tab' => $request->string('tab')->toString() ?: 'notifications'])
            ->with('success', __('storefront.account.notification_marked_read'));
    }

    /**
     * Mark all customer database notifications as read.
     */
    public function markAllNotificationsRead(): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $customer->unreadNotifications->markAsRead();

        return redirect()
            ->route('customer.account', ['tab' => 'notifications'])
            ->with('success', __('storefront.account.notifications_marked_read'));
    }

    public function showForgotPassword(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.account');
        }

        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendPasswordResetCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $customer = $this->findActiveCustomerByLogin($validated['login']);

        if (! $customer || ! $customer->password) {
            throw ValidationException::withMessages([
                'login' => __('storefront.auth.reset_not_found'),
            ]);
        }

        $code = CustomerOtp::issue(CustomerOtp::PURPOSE_RESET, $customer);
        $this->deliverCustomerCode($customer, $code, reset: true);

        $request->session()->put('customer_password_reset_id', $customer->id);

        return redirect()
            ->route('customer.password.reset')
            ->with('success', __('storefront.auth.reset_code_sent'));
    }

    public function showResetPassword(Request $request): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.account');
        }

        if (! $request->session()->get('customer_password_reset_id')) {
            return redirect()->route('customer.password.forgot');
        }

        return Inertia::render('Auth/ResetPassword');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $customerId = $request->session()->get('customer_password_reset_id');

        if (! $customerId) {
            return redirect()->route('customer.password.forgot');
        }

        $validated = $request->validate([
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $customer = Customer::query()->whereKey($customerId)->where('is_active', true)->first();

        if (! $customer) {
            $request->session()->forget('customer_password_reset_id');

            return redirect()->route('customer.password.forgot');
        }

        CustomerOtp::verify(CustomerOtp::PURPOSE_RESET, $customer, $validated['code']);

        $customer->password = $validated['password'];
        $customer->save();

        $request->session()->forget('customer_password_reset_id');

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.account')->with('success', __('storefront.auth.password_reset_done'));
    }

    public function sendLoginOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $customer = $this->findActiveCustomerByLogin($validated['login']);

        if (! $customer || ! $customer->password) {
            throw ValidationException::withMessages([
                'login' => __('storefront.auth.otp_not_found'),
            ]);
        }

        $code = CustomerOtp::issue(CustomerOtp::PURPOSE_LOGIN, $customer);
        $this->deliverCustomerCode($customer, $code, reset: false);

        $request->session()->put('customer_otp_login_id', $customer->id);

        return back()->with('success', __('storefront.auth.otp_sent'));
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $customerId = $request->session()->get('customer_otp_login_id');

        $validated = $request->validate([
            'code' => 'required|string|size:6',
            'remember' => 'sometimes|boolean',
        ]);

        if (! $customerId) {
            throw ValidationException::withMessages([
                'code' => __('storefront.auth.otp_request_first'),
            ]);
        }

        $customer = Customer::query()->whereKey($customerId)->where('is_active', true)->first();

        if (! $customer) {
            $request->session()->forget('customer_otp_login_id');

            throw ValidationException::withMessages([
                'code' => __('storefront.auth.otp_request_first'),
            ]);
        }

        CustomerOtp::verify(CustomerOtp::PURPOSE_LOGIN, $customer, $validated['code']);
        $request->session()->forget('customer_otp_login_id');

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('success', __('storefront.auth.welcome', ['name' => $customer->name]));
    }

    protected function findActiveCustomerByLogin(string $loginInput): ?Customer
    {
        $loginInput = trim($loginInput);
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $customerQuery = Customer::query()->where('is_active', true);

        if ($isEmail) {
            return $customerQuery->where('email', $loginInput)->first();
        }

        $cleaned = preg_replace('/[^\d]/', '', $loginInput) ?: '';

        return $customerQuery->where(function ($q) use ($loginInput, $cleaned) {
            $q->where('phone', $loginInput)
                ->orWhere('phone', $cleaned);
            if (str_starts_with($cleaned, '8801')) {
                $q->orWhere('phone', substr($cleaned, 2));
            }
        })->first();
    }

    protected function deliverCustomerCode(Customer $customer, string $code, bool $reset): void
    {
        $shopName = (string) (Setting::get('site_name', 'Nobo Shop') ?: 'Nobo Shop');
        $smsBody = $reset
            ? __('storefront.auth.reset_sms', ['code' => $code, 'shop' => $shopName])
            : __('storefront.auth.otp_sms', ['code' => $code, 'shop' => $shopName]);

        try {
            app(SmsService::class)->send($customer->phone, $smsBody);
        } catch (\Throwable) {
            // Continue — email may still deliver.
        }

        if (filled($customer->email)) {
            try {
                Mail::to($customer->email)->send(new CustomerPasswordResetMail($customer, $code, $shopName, $reset));
            } catch (\Throwable) {
                // SMS may still have delivered.
            }
        }
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
                    'current_password' => __('storefront.auth.wrong_password'),
                ]);
            }
            $customer->password = $validated['new_password'];
        }

        $customer->name = $validated['name'];
        $customer->email = $validated['email'] ?? $customer->email;
        $customer->save();

        return back()->with('success', __('storefront.auth.profile_updated'));
    }
}
