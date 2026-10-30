<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send a single SMS to a recipient phone number.
     */
    public function send(string $to, string $message): bool
    {
        $enabled = (bool) Setting::get('sms_enabled', config('services.sms.enabled', true));
        if (! $enabled) {
            return false;
        }

        $formattedTo = $this->formatBangladeshiPhone($to);
        if (! $formattedTo) {
            Log::warning("SmsService: Invalid phone number '{$to}'");

            return false;
        }

        $driver = Setting::get('sms_driver', config('services.sms.driver', 'log'));

        try {
            return match ($driver) {
                'greenweb' => $this->sendViaGreenweb($formattedTo, $message),
                'bulksmsbd' => $this->sendViaBulkSmsBd($formattedTo, $message),
                default => $this->sendViaLog($formattedTo, $message),
            };
        } catch (Exception $e) {
            Log::error("SmsService failed to send SMS to {$formattedTo}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Send order placement confirmation SMS to customer.
     */
    public function sendOrderPlacedSms(Order $order): bool
    {
        $siteName = Setting::get('site_name', 'Nobo Shop');
        $grandTotal = number_format((float) $order->grand_total);
        $trackUrl = route('track.order', ['order_number' => $order->order_number]);

        $message = "প্রিয় {$order->customer_name}, {$siteName}-এ আপনার অর্ডার #{$order->order_number} সফলভাবে গ্রহণ করা হয়েছে। মোট: ৳{$grandTotal}। অর্ডার ট্র্যাক করতে ভিজিট করুন: {$trackUrl}";

        return $this->send($order->customer_phone, $message);
    }

    /**
     * Send order status update SMS to customer.
     */
    public function sendOrderStatusUpdatedSms(Order $order, string $statusBengali): bool
    {
        $siteName = Setting::get('site_name', 'Nobo Shop');
        $trackUrl = route('track.order', ['order_number' => $order->order_number]);

        $message = "প্রিয় {$order->customer_name}, {$siteName}-এ আপনার অর্ডার #{$order->order_number} এর স্ট্যাটাস আপডেট হয়েছে: {$statusBengali}। বিস্তারিত: {$trackUrl}";

        return $this->send($order->customer_phone, $message);
    }

    /**
     * Send registration welcome SMS to newly registered customer.
     */
    public function sendCustomerRegistrationSms(Customer $customer): bool
    {
        $siteName = Setting::get('site_name', 'Nobo Shop');
        $message = "স্বাগতম {$customer->name}! {$siteName}-এ আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে। কেনাকাটা শুরু করুন: ".url('/');

        return $this->send($customer->phone, $message);
    }

    /**
     * Standardize phone number format for Bangladeshi operators (e.g. 8801XXXXXXXXX).
     */
    public function formatBangladeshiPhone(string $phone): ?string
    {
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($cleaned, '8801') && strlen($cleaned) === 13) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '01') && strlen($cleaned) === 11) {
            return '88'.$cleaned;
        }

        if (str_starts_with($cleaned, '1') && strlen($cleaned) === 10) {
            return '880'.$cleaned;
        }

        return strlen($cleaned) >= 10 ? $cleaned : null;
    }

    /**
     * Log SMS for local development and testing.
     */
    protected function sendViaLog(string $to, string $message): bool
    {
        Log::info("SMS [LOG DRIVER] to {$to}: {$message}");

        return true;
    }

    /**
     * Send SMS via Greenweb BD API.
     */
    protected function sendViaGreenweb(string $to, string $message): bool
    {
        $token = Setting::get('greenweb_token', config('services.greenweb.token'));
        if (! $token) {
            Log::warning('SmsService: Greenweb token is missing.');

            return false;
        }

        $response = Http::asForm()->timeout(10)->post('https://api.greenweb.com.bd/api.php', [
            'token' => $token,
            'to' => $to,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info("SmsService: Greenweb SMS sent successfully to {$to}");

            return true;
        }

        Log::error("SmsService: Greenweb API error: {$response->body()}");

        return false;
    }

    /**
     * Send SMS via BulkSMSBD API.
     */
    protected function sendViaBulkSmsBd(string $to, string $message): bool
    {
        $apiKey = Setting::get('bulksmsbd_api_key', config('services.bulksmsbd.api_key'));
        $senderId = Setting::get('bulksmsbd_sender_id', config('services.bulksmsbd.sender_id'));

        if (! $apiKey) {
            Log::warning('SmsService: BulkSMSBD API key is missing.');

            return false;
        }

        $response = Http::timeout(10)->post('http://bulksmsbd.net/api/smsapi', [
            'api_key' => $apiKey,
            'type' => 'text',
            'number' => $to,
            'senderid' => $senderId,
            'message' => $message,
        ]);

        if ($response->successful()) {
            Log::info("SmsService: BulkSMSBD SMS sent successfully to {$to}");

            return true;
        }

        Log::error("SmsService: BulkSMSBD API error: {$response->body()}");

        return false;
    }
}
