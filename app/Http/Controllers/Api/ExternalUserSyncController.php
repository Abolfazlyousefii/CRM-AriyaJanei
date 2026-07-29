<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ErpUserSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** @deprecated Use GET /api/integrations/erp/users. */
class ExternalUserSyncController extends Controller
{
    public function index(Request $request, ErpUserSyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        Log::warning('Deprecated external user sync endpoint used');

        return response()->json($service->legacyPage($validated, $request));
    }
}
