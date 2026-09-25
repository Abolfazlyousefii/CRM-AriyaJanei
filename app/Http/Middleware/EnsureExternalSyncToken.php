<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalSyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('services.external_sync.token');

        if (blank($expectedToken)) {
            $response = response()->json([
                'error' => [
                    'code' => 'sync_token_not_configured',
                    'message' => 'ERP synchronization is not configured.',
                ],
            ], 500);

            $this->audit($request, $response, 0);

            return $response;
        }

        $providedToken = $request->bearerToken();

        if (! hash_equals($expectedToken, (string) $providedToken)) {
            $response = response()->json([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Authentication is required.',
                ],
            ], 401);

            $this->audit($request, $response, 0);

            return $response;
        }

        $response = $next($request);
        $data = method_exists($response, 'getData') ? $response->getData(true) : [];
        $count = count($data['data'] ?? $data['users']['data'] ?? []);
        $this->audit($request, $response, $count);

        return $response;
    }

    private function audit(Request $request, Response $response, int $count): void
    {
        if (! config('services.erp.sync_audit_enabled')) {
            return;
        }

        activity('erp-sync')->withProperties([
            'endpoint' => $request->path(),
            'result' => $response->isSuccessful() ? 'success' : 'failure',
            'returned_users' => $count,
            'cursor' => max(0, $request->integer('cursor')),
            'http_status' => $response->getStatusCode(),
        ])->log('ERP user synchronization');
    }
}
