<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\MeController as AdminMeController;
use App\Http\Controllers\Api\Admin\Devices\IndexController as AdminDeviceIndexController;
use App\Http\Controllers\Api\Admin\Users\IndexController as AdminUserIndexController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RefreshController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Devices\DestroyController as DeviceDestroyController;
use App\Http\Controllers\Api\Devices\IndexController as DeviceIndexController;
use App\Http\Controllers\Api\Devices\StoreController as DeviceStoreController;
use App\Http\Controllers\Api\Devices\StreamController as DeviceStreamController;
use App\Http\Controllers\Api\Devices\UpdateController as DeviceUpdateController;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('refresh', RefreshController::class);

        Route::middleware(AuthenticateJwt::class)->group(function (): void {
            Route::get('me', MeController::class);
            Route::post('logout', LogoutController::class);
        });
    });

    Route::middleware(AuthenticateJwt::class)->group(function (): void {
        Route::get('devices', DeviceIndexController::class);
        Route::post('devices', DeviceStoreController::class);
        Route::put('devices/{device}', DeviceUpdateController::class)->whereNumber('device');
        Route::delete('devices/{device}', DeviceDestroyController::class)->whereNumber('device');
        Route::get('devices/{device}/stream', DeviceStreamController::class)->whereNumber('device');
    });

    Route::prefix('admin')
        ->middleware([AuthenticateJwt::class, EnsureAdmin::class])
        ->group(function (): void {
            Route::get('me', AdminMeController::class);
            Route::get('users', AdminUserIndexController::class);
            Route::get('devices', AdminDeviceIndexController::class);
        });
});
