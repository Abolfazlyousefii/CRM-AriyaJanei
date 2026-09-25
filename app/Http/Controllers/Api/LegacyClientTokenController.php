<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** @deprecated Remove after the ERP SSO migration is complete. This base64 payload is not a secure token. */
class LegacyClientTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('services.legacy_client_token.enabled')) {
            return response()->json(['error' => 'This legacy endpoint is no longer available.'], 410);
        }

        $validated = $request->validate(['phone' => ['required', 'string'], 'secret' => ['required', 'string']]);
        $user = User::where('phone', $validated['phone'])->first();
        $expected = (string) config('services.legacy_client_token.secret');
        if (! $user || $expected === '' || ! hash_equals($expected, $validated['secret'])) {
            Log::warning('Legacy client token request rejected', ['phone' => $this->mask($validated['phone'])]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = base64_encode((string) json_encode([
            'id' => $user->id, 'phone' => $user->phone, 'name' => $user->name, 'exp' => time() + 123600,
        ]));
        Log::warning('Deprecated legacy client token issued', ['phone' => $this->mask($user->phone)]);

        return response()->json(['token' => $payload]);
    }

    private function mask(string $phone): string
    {
        return substr($phone, 0, 4).'***'.substr($phone, -2);
    }
}
