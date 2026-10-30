<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Enums\ShippingProvider;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_number' => null, // Auto-generated in model boot
            'order_id' => Order::factory(),
            'customer_id' => null,
            'shipping_method_id' => null,
            'provider' => fake()->randomElement([
                ShippingProvider::Steadfast,
                ShippingProvider::Pathao,
                ShippingProvider::InHouse,
                ShippingProvider::RedX,
            ]),
            'status' => ShipmentStatus::Pending,
            'tracking_code' => 'TRK'.fake()->unique()->numerify('########'),
            'shipping_charge' => fake()->randomElement([70.00, 130.00]),
            'weight_kg' => fake()->randomFloat(2, 0.25, 2.50),
            'recipient_name' => fake()->name(),
            'recipient_phone' => '01'.fake()->numerify('#########'),
            'shipping_address_line' => fake()->streetAddress(),
            'shipping_area' => fake()->citySuffix(),
            'shipping_city' => 'Dhaka',
            'shipping_postcode' => fake()->postcode(),
            'shipping_country' => 'Bangladesh',
            'packed_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'returned_at' => null,
            'cancelled_at' => null,
            'dispatched_by' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the shipment has been packed.
     */
    public function packed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Packed,
            'packed_at' => now(),
        ]);
    }

    /**
     * Indicate that the shipment is in transit.
     */
    public function shipped(?string $trackingCode = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::InTransit,
            'shipped_at' => now(),
            'tracking_code' => $trackingCode ?? ('TRK'.fake()->unique()->numerify('########')),
        ]);
    }

    /**
     * Indicate that the shipment has been delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Delivered,
            'shipped_at' => now()->subDay(),
            'delivered_at' => now(),
        ]);
    }

    /**
     * Indicate that the shipment was returned to origin.
     */
    public function returned(?string $reason = 'Customer unreachable'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Returned,
            'shipped_at' => now()->subDays(2),
            'returned_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * Indicate that the shipment was cancelled.
     */
    public function cancelled(?string $reason = 'Order cancelled'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Cancelled,
            'cancelled_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * Associate shipment with a specific order, copying recipient details.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id ?? Customer::where('user_id', $order->user_id)->value('id'),
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'shipping_address_line' => $order->shipping_address_line,
            'shipping_area' => $order->shipping_area,
            'shipping_city' => $order->shipping_city ?? 'Dhaka',
            'shipping_postcode' => $order->shipping_postcode,
            'shipping_country' => $order->shipping_country ?? 'Bangladesh',
            'shipping_charge' => (float) $order->shipping_amount,
        ]);
    }

    /**
     * Associate an actor user who dispatched or recorded the shipment.
     */
    public function dispatchedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'dispatched_by' => $user->id,
        ]);
    }
}
