<?php

namespace Database\Factories;

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRequestStatusHistory>
 */
class ReturnRequestStatusHistoryFactory extends Factory
{
    protected $model = ReturnRequestStatusHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'changed_by' => null,
            'from_status' => null,
            'to_status' => ReturnRequestStatus::Pending,
            'reason' => null,
            'created_at' => now(),
        ];
    }
}
