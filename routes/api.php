<?php

use App\Http\Controllers\Api\ExternalCustomerSyncController;
use App\Http\Controllers\Api\ExternalUserSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

// Endpoint برای دادن token بر اساس شماره تماس
Route::post('/token-for-client', function (Request $request) {
    \Log::info('Request received', [
        'phone' => $request->phone,
        'secret' => $request->secret,
    ]);

    $request->validate([
        'phone' => 'required|string',
        'secret' => 'required|string',
    ]);

    $user = User::where('phone', $request->phone)->first();

    if (!$user || $request->secret !== env('CLIENT_SECRET')) {
        \Log::warning('Unauthorized', ['phone' => $request->phone]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $token = base64_encode(json_encode([
        'id' => $user->id,
        'phone' => $user->phone,
        'name' => $user->name,
        'exp' => time() + 123600,
    ]));

    \Log::info('Token issued', ['phone' => $user->phone]);

    return response()->json(['token' => $token]);
});
Route::middleware('external.sync')->group(function () {
    Route::get('/external/users', [ExternalUserSyncController::class, 'index']);
});
Route::get('/external/customers', [ExternalCustomerSyncController::class, 'index']);
Route::post('/external/customers', [ExternalCustomerSyncController::class, 'store']);
