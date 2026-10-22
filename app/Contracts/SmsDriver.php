<?php

namespace App\Contracts;

interface SmsDriver {
    public function send( string $mobile, string $message ): array;

    public function sendPattern( string $mobile, string $pattern, array $parameters = [] ): array;

    public function getCredit(): array;

    public function getBaseNumbers(): array;

    public function getMessages( int $location, int $index, int $count, string $from = '' ): array;

    public function getDeliveries( array $recId, int $status = 0 ): array;

    public function getMessagesByDate( string $fromDate, string $toDate, string $message = '' ): array;
}