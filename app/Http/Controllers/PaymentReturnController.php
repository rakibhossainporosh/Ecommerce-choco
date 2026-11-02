<?php

namespace App\Http\Controllers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayMode;
use App\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentReturnController extends Controller
{
    public function returned(Request $request, string $gateway, string $outcome, PaymentManager $payments): RedirectResponse
    {
        $driver = $this->redirectGateway($payments, $gateway);

        try {
            $result = $driver->completeReturn($request, $outcome);
        } catch (PaymentGatewayException $exception) {
            return redirect()
                ->route('checkout.index')
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        if ($result->wasPaid() && filled($result->orderNumber)) {
            return redirect()->route('checkout.success', ['order_number' => $result->orderNumber]);
        }

        return redirect()
            ->route('checkout.index')
            ->withErrors(['payment' => $result->message]);
    }

    public function ipn(Request $request, string $gateway, PaymentManager $payments): Response
    {
        $driver = $this->redirectGateway($payments, $gateway);

        try {
            $driver->completeWebhook($request);
        } catch (PaymentGatewayException) {
            return response('temporarily unavailable', 503);
        }

        return response('OK');
    }

    private function redirectGateway(PaymentManager $payments, string $gateway): PaymentGateway
    {
        $driver = $payments->find($gateway);
        abort_unless($driver && $driver->mode() === PaymentGatewayMode::Redirect, 404);

        return $driver;
    }
}
