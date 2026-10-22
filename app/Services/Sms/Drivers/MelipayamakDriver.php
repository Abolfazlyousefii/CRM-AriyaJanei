<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsDriver;
use Melipayamak\MelipayamakApi;

class MelipayamakDriver implements SmsDriver {
    public function __construct() {
        $this->api = new MelipayamakApi(config('sms.drivers.melipayamak.username'), config('sms.drivers.melipayamak.password'));
    }

    private MelipayamakApi $api;

    public function getBaseNumbers(): array {
        $response = $this->api->sms()
            ->getBaseNumbers();

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function getCredit(): array {
        $response = $this->api->sms()
            ->getCredit();

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function getDeliveries( array $recId, int $status = 0 ): array {
        $response = $this->api->sms()
            ->getDeliveries2($recId, $status);

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function getMessages( int $location, int $index, int $count, string $from = '' ): array {
        $response = $this->api->sms()
            ->getMessages($location, $index, $count, $from);

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function getMessagesByDate( string $fromDate, string $toDate, string $message = '' ): array {
        $response = $this->api->sms()
            ->getMessagesByDate($fromDate, $toDate, $message);

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function send( string $mobile, string $message ): array {
        $response = $this->api->sms()
            ->send($mobile, $message);

        return [
            'driver'   => 'melipayamak',
            'response' => $response,
        ];
    }

    public function sendPattern( string $mobile, string $pattern, array $parameters = [] ): array {
        $response = $this->api->sms()
            ->sendByBaseNumber($parameters, $mobile, $pattern);

        return [
            'driver'   => 'melipayamak',
            'pattern'  => $pattern,
            'response' => $response,
        ];
    }
}