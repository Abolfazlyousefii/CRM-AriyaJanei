<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriver;
use App\Services\Sms\Drivers\MelipayamakApi;
use App\Services\Sms\Drivers\MelipayamakDriver;
use App\Services\Sms\Drivers\SandboxDriver;
use InvalidArgumentException;

class SmsManager {
    public function __construct() {
        $this->drivers = [
            'melipayamak'     => app(MelipayamakDriver::class),
            'melipayamak_api' => app(MelipayamakApi::class),
            'sandbox'         => app(SandboxDriver::class),
        ];
    }

    private array $drivers = [];

    public function driver( ?string $name = null ): SmsDriver {
        $name ??= config('sms.default');

        if ( !isset($this->drivers[$name]) ) {
            throw new InvalidArgumentException("SMS driver [$name] not found.");
        }

        return $this->drivers[$name];
    }

    public function send( string $mobile, string $message ): array {
        return $this->driver()
            ->send($mobile, $message);
    }

    public function sendPattern( string $mobile, string $pattern, array $parameters = [] ): array {
        return $this->driver()
            ->sendPattern($mobile, $pattern, $parameters);
    }

    public function getCredit(): array {
        return $this->driver()
            ->getCredit();
    }

    public function getBaseNumbers(): array {
        return $this->driver()
            ->getBaseNumbers();
    }

    public function getMessages( int $location, int $index, int $count, string $from = '' ): array {
        return $this->driver()
            ->getMessages($location, $index, $count, $from);
    }

    public function getDeliveries( array $recId, int $status = 0 ): array {
        return $this->driver()
            ->getDeliveries($recId, $status);
    }

    public function getMessagesByDate( string $fromDate, string $toDate, string $message = '' ): array {
        return $this->driver()
            ->getMessagesByDate($fromDate, $toDate, $message);
    }
}