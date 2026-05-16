<?php

use App\Http\Controllers\Api\ExternalUserSyncController;
use Illuminate\Support\Facades\Route;


    Route::get('/external/users', [ExternalUserSyncController::class, 'index']);

