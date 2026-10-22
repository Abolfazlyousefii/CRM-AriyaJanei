<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FieldVisitLocation
{
    public function reverse(float $lat, float $lng): array
    {
        $unavailable = ['available' => false, 'city' => '', 'address' => ''];
        if (! config('field_visits.reverse_enabled')) {
            return $unavailable;
        }
        $key = 'field-visit-address:'.sprintf('%.5f:%.5f', $lat, $lng);
        if ($cached = Cache::get($key)) {
            return $cached;
        }
        $lock = Cache::lock('field-visit-reverse-provider', 10);
        if (! $lock->get()) {
            return $unavailable;
        }
        try {
            // Keep the application-wide request interval above one second, including concurrent users.
            if (microtime(true) - Cache::get('field-visit-reverse-last', 0) < 1.1) {
                return $unavailable;
            }
            Cache::put('field-visit-reverse-last', microtime(true), 60);
            $response = Http::connectTimeout(2)->timeout(5)->withHeaders([
                'User-Agent' => 'AriyaCRM-FieldVisits/1.0 ('.config('app.url').')',
                'Accept' => 'application/json',
            ])->get(config('field_visits.reverse_url'), ['lat' => $lat, 'lon' => $lng, 'format' => 'jsonv2', 'addressdetails' => 1, 'accept-language' => 'fa', 'zoom' => 18]);
            $data = $response->successful() ? $response->json() : null;
            $address = is_array($data) && is_array($data['address'] ?? null) ? $data['address'] : [];
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? '';
            $street = implode('، ', array_filter([$address['suburb'] ?? '', $address['road'] ?? '', $address['house_number'] ?? '']));
            $text = $street ?: ($data['display_name'] ?? '');
            $result = ['available' => is_string($city) && is_string($text) && ($city !== '' || $text !== ''),
                'city' => is_string($city) ? mb_substr($city, 0, 60) : '', 'address' => is_string($text) ? mb_substr($text, 0, 200) : ''];
            Cache::put($key, $result, $result['available'] ? now()->addDays(7) : now()->addMinute());

            return $result;
        } catch (\Throwable $e) {
            Cache::put($key, $unavailable, 30);

            return $unavailable;
        } finally {
            $lock->release();
        }
    }
}
