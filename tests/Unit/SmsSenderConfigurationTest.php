<?php

namespace Tests\Unit;

use App\Services\Sms\Drivers\MelipayamakApi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsSenderConfigurationTest extends TestCase
{
    public function test_placeholder_or_missing_sender_never_calls_the_provider(): void
    {
        Http::preventStrayRequests();
        config(['sms.drivers.melipayamak_api.token' => 'test-token']);
        foreach ([null, '', '5000xxx'] as $sender) {
            config(['sms.drivers.melipayamak_api.from' => $sender]);
            $result = app(MelipayamakApi::class)->send('09120000000', 'test');
            $this->assertSame('failed', $result['status'] ?? null);
            $this->assertSame('invalid_sender', $result['error_code'] ?? null);
        }
        Http::assertNothingSent();
    }

    public function test_configured_sender_and_recipient_are_sent_unchanged(): void
    {
        config(['sms.drivers.melipayamak_api.token' => 'test-token', 'sms.drivers.melipayamak_api.from' => '50001234']);
        Http::fake(['console.melipayamak.com/*' => Http::response(['recId' => '123456', 'status' => 'ارسال موفق بود'])]);
        $result = app(MelipayamakApi::class)->send('09120000000', 'test');
        $this->assertSame('success', $result['status']);
        Http::assertSent(fn ($request) => $request['from'] === '50001234' && $request['to'] === '09120000000');
    }
}
