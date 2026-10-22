<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsDriver;
use Faker\Factory;

class SandboxDriver implements SmsDriver {
    public function __construct() {
        $this->faker = Factory::create();
    }

    private $faker;

    public function getBaseNumbers(): array {
        return [
            'driver'   => 'sandbox',
            'response' => [ 'pattern_1', 'pattern_2' ],
        ];
    }

    public function getCredit(): array {
        return [
            'driver'   => 'sandbox',
            'response' => 5000.50,
        ];
    }

    public function getDeliveries( array $recId, int $status = 0 ): array {
        return [
            'driver'   => 'sandbox',
            'response' => [],
        ];
    }

    public function getMessages( int $location, int $index, int $count, string $from = '' ): array {
        return [
            'driver'   => 'sandbox',
            'response' => [],
        ];
    }

    public function getMessagesByDate( string $fromDate, string $toDate, string $message = '' ): array {
        return [
            'driver'   => 'sandbox',
            'response' => [],
        ];
    }

    public function send( string $mobile, string $message ): array {
        return [
            'driver'        => 'sandbox',
            'type'          => 'normal',
            'mobile'        => $mobile,
            'message'       => $message,
            'tracking_code' => $this->faker->uuid(),
            'status'        => 'sent',
            'sent_at'       => now(),
        ];
    }

    public function sendPattern( string $mobile, string $pattern, array $parameters = [] ): array {
        return [
            'driver'        => 'sandbox',
            'type'          => 'pattern',
            'mobile'        => $mobile,
            'pattern'       => $pattern,
            'parameters'    => $parameters,
            'tracking_code' => $this->faker->uuid(),
            'status'        => 'sent',
            'sent_at'       => now()->toDateTimeLocalString(),
        ];
    }
}
