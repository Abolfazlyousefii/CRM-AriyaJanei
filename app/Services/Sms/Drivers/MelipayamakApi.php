<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MelipayamakApi implements SmsDriver {
    public function __construct() {
        $this->token       = config('sms.drivers.melipayamak_api.token');
        $this->defaultFrom = config('sms.drivers.melipayamak_api.from', null);
    }

    private string  $token;
    private string  $baseUrl = 'https://console.melipayamak.com/api';
    private ?string $defaultFrom;

    private function request( string $method, string $endpoint, array $data = [] ): array {
        $url = "{$this->baseUrl}/{$endpoint}/{$this->token}";

        try {
            $response = Http::withoutVerifying()
                ->asJson()
                ->timeout(10)
                ->{$method}($url, $data);

            return [
                'driver'   => __CLASS__,
                'endpoint' => $endpoint,
                'response' => $response->json(),
                'status'   => $response->successful() ? 'success' : 'failed',
            ];
        } catch ( ConnectionException $e ) {
            return [
                'driver'   => __CLASS__,
                'endpoint' => $endpoint,
                'response' => [ 'status' => 'خطا در اتصال به سرور: ' . $e->getMessage() ],
                'status'   => 'error',
            ];
        } catch ( \Exception $e ) {
            return [
                'driver'   => __CLASS__,
                'endpoint' => $endpoint,
                'response' => [ 'status' => 'خطای غیرمنتظره: ' . $e->getMessage() ],
                'status'   => 'error',
            ];
        }
    }

    // ---------------------------------------------------------
    // پیاده‌سازی متدهای اینترفیس SmsDriver
    // ---------------------------------------------------------

    public function getBaseNumbers(): array {
        return [ 'driver' => 'melipayamak_api', 'response' => [ 'status' => 'در این متد API پشتیبانی نمی‌شود' ] ];
    }

    public function getCredit(): array {
        return $this->request('get', 'receive/credit');
    }

    public function getDeliveries( array $recId, int $status = 0 ): array {
        return [ 'driver' => 'melipayamak_api', 'response' => [ 'status' => 'در این متد API پشتیبانی نمی‌شود' ] ];
    }

    // ---------------------------------------------------------
    // متدهایی که در این نسخه از API مستقیماً پشتیبانی نمی‌شوند
    // اما به دلیل الزام اینترفیس باید وجود داشته باشند
    // ---------------------------------------------------------

    public function getMessages( int $location, int $index, int $count, string $from = '' ): array {
        return [ 'driver' => 'melipayamak_api', 'response' => [ 'status' => 'در این متد API پشتیبانی نمی‌شود' ] ];
    }

    public function getMessagesByDate( string $fromDate, string $toDate, string $message = '' ): array {
        return [ 'driver' => 'melipayamak_api', 'response' => [ 'status' => 'در این متد API پشتیبانی نمی‌شود' ] ];
    }

    public function send( string $mobile, string $message ): array {
        if ( !preg_match('/^\+?[0-9]{4,16}$/', (string) $this->defaultFrom) ) {
            return [
                'driver'   => 'melipayamak_api',
                'status' => 'failed',
                'error_code' => 'invalid_sender',
                'response' => [ 'status' => 'شماره خط فرستنده پیامک معتبر نیست؛ مدیر سیستم باید خط فعال پنل را تنظیم کند.' ],
            ];
        }

        return $this->request('post', 'send/simple', [
            'from' => $this->defaultFrom,
            'to'   => $mobile,
            'text' => $message,
        ]);
    }

    public function sendPattern( string $mobile, string $pattern, array $parameters = [] ): array {
        return $this->request('post', 'send/shared', [
            'bodyId' => (int) $pattern,
            'to'     => $mobile,
            'args'   => $parameters,
        ]);
    }
}
