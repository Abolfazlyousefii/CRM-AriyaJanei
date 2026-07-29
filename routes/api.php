<?php

use App\Http\Controllers\Api\ExternalCustomerSyncController;
use App\Http\Controllers\Api\ExternalUserSyncController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ErpUserSyncController;
use App\Http\Controllers\Api\LegacyClientTokenController;

Route::post('/token-for-client', LegacyClientTokenController::class);
Route::middleware(['external.sync', 'throttle:erp.sync'])->group(function () {
    Route::get('/integrations/erp/users', ErpUserSyncController::class);
    Route::get('/external/users', [ExternalUserSyncController::class, 'index']);
});
Route::get('/external/customers', [ExternalCustomerSyncController::class, 'index']);
Route::post('/external/customers', [ExternalCustomerSyncController::class, 'store']);
