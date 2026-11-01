<?php

namespace App\Console\Commands;

use App\Services\AbandonedCartService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('carts:remind-abandoned')]
#[Description('Send one reminder for carts that were left without an order')]
class RemindAbandonedCarts extends Command
{
    public function handle(AbandonedCartService $carts): int
    {
        $sent = $carts->remindDue();

        $this->info("Reminded {$sent} abandoned carts.");

        return self::SUCCESS;
    }
}
