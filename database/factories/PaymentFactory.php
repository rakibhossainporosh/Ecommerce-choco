<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentTransactionStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => null, // Booted hook will automatically generate PAY-YYYYMMDD-XXXX
            'order_id' => Order::factory(),
            'customer_id' => null,
            'payment_method' => fake()->randomElement([
                PaymentMethod::Bkash,
                PaymentMethod::Nagad,
                PaymentMethod::Rocket,
                PaymentMethod::BankTransfer,
                PaymentMethod::Cod,
            ]),
            'status' => PaymentTransactionStatus::Completed,
            'amount' => fake()->randomFloat(2, 50, 2000),
            'currency' => 'BDT',
            'transaction_id' => 'TRX'.fake()->unique()->numerify('########'),
            'account_number' => '01'.fake()->numerify('#########'),
            'paid_at' => now(),
            'recorded_by' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the payment is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransactionStatus::Pending,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the payment has failed.
     */
    public function failed(?string $reason = 'Gateway transaction declined'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransactionStatus::Failed,
            'notes' => $reason,
        ]);
    }

    /**
     * Indicate that the payment was refunded.
     */
    public function refunded(?string $reason = 'Customer requested refund'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransactionStatus::Refunded,
            'notes' => $reason,
        ]);
    }

    /**
     * Associate the payment with a specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'currency' => $order->currency ?? 'BDT',
            'customer_id' => Customer::where('user_id', $order->user_id)->value('id'),
        ]);
    }

    /**
     * Associate the payment with an actor who recorded it.
     */
    public function recordedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_by' => $user->id,
        ]);
    }
}
