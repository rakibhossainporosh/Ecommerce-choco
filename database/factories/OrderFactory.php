<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 200, 5000);
        $shipping = 100.00;
        $discount = 0.00;
        $grandTotal = round($subtotal - $discount + $shipping, 2);

        return [
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'user_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => '01'.fake()->numerify('#########'),
            'customer_email' => fake()->safeEmail(),
            'customer_note' => null,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => PaymentMethod::Cod,
            'currency' => 'BDT',
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'grand_total' => $grandTotal,
            'shipping_address_line' => fake()->streetAddress(),
            'shipping_area' => fake()->citySuffix(),
            'shipping_city' => 'Dhaka',
            'shipping_postcode' => fake()->postcode(),
            'shipping_country' => 'Bangladesh',
            'billing_same_as_shipping' => true,
            'billing_address_line' => null,
            'billing_area' => null,
            'billing_city' => null,
            'billing_postcode' => null,
            'billing_country' => null,
            'placed_at' => now(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Indicate that the order belongs to a registered customer.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user) {
            $customer = $user ?? User::factory()->create();

            return [
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
            ];
        });
    }

    /**
     * Indicate that the order has different billing address.
     */
    public function withDifferentBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_same_as_shipping' => false,
            'billing_address_line' => fake()->streetAddress(),
            'billing_area' => fake()->citySuffix(),
            'billing_city' => 'Chittagong',
            'billing_postcode' => fake()->postcode(),
            'billing_country' => 'Bangladesh',
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(?User $cancelledBy = null, string $reason = 'Customer requested cancellation'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy?->id,
            'cancellation_reason' => $reason,
        ]);
    }
}
