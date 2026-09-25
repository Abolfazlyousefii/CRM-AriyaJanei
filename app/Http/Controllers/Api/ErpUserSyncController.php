<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ErpUserSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErpUserSyncController extends Controller
{
    public function __invoke(Request $request, ErpUserSyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'cursor' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'updated_since' => ['nullable', 'date'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);
        $validated['include_inactive'] = $request->has('include_inactive')
            ? $request->boolean('include_inactive')
            : true;
        $result = $service->cursorPage($validated, $request);

        return response()->json($result);
    }
}
