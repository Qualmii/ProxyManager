<?php

use App\Http\Controllers\Api\ProxyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('proxies/check-all', [ProxyController::class, 'checkAll']);
    Route::post('proxies/{proxy}/check', [ProxyController::class, 'check']);
    Route::apiResource('proxies', ProxyController::class);
});

