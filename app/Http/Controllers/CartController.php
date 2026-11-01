<?php

namespace App\Http\Controllers;

use App\Enums\AbandonedCartStatus;
use App\Models\AbandonedCart;
use App\Services\AbandonedCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function remember(Request $request, AbandonedCartService $carts): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:255'],
            'items' => ['present', 'array', 'max:30'],
            'items.*.id' => ['required', 'integer'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $carts->remember($request->user('customer'), $validated);

        return response()->json(['ok' => true]);
    }

    public function recover(string $token): Response
    {
        $cart = AbandonedCart::query()->where('token', $token)->first();
        $items = [];

        if ($cart !== null && in_array($cart->status, [AbandonedCartStatus::Open, AbandonedCartStatus::Reminded], true)) {
            $items = is_array($cart->items) ? $cart->items : [];
        }

        return Inertia::render('RecoverCart', [
            'items' => $items,
        ]);
    }
}
