<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $validated = $this->validatedAddress($request);
        $validated['customer_id'] = $customer->id;
        $validated['type'] = 'shipping';
        $validated['is_default'] = $request->boolean('is_default')
            || ! $customer->addresses()->where('type', 'shipping')->exists();

        CustomerAddress::query()->create($validated);

        return back()->with('success', __('storefront.account.address_saved'));
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer || (int) $address->customer_id !== (int) $customer->id) {
            abort(403);
        }

        $validated = $this->validatedAddress($request);
        $address->fill($validated);

        if ($request->boolean('is_default')) {
            $address->is_default = true;
        }

        $address->save();

        return back()->with('success', __('storefront.account.address_updated'));
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer || (int) $address->customer_id !== (int) $customer->id) {
            abort(403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $customer->addresses()->where('type', 'shipping')->latest('id')->first();
            $next?->setAsDefault();
        }

        return back()->with('success', __('storefront.account.address_deleted'));
    }

    public function makeDefault(CustomerAddress $address): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer || (int) $address->customer_id !== (int) $customer->id) {
            abort(403);
        }

        $address->setAsDefault();

        return back()->with('success', __('storefront.account.address_default_set'));
    }

    /**
     * @return array{name: ?string, phone: ?string, address_line: string, area: ?string, city: string, postcode: ?string, country: string}
     */
    private function validatedAddress(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_line' => ['required', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $validated['country'] = $validated['country'] ?? 'Bangladesh';

        return $validated;
    }
}
