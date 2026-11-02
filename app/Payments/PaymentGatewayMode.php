<?php

namespace App\Payments;

enum PaymentGatewayMode: string
{
    case Cod = 'cod';
    case Manual = 'manual';
    case Redirect = 'redirect';
}
