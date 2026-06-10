<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/tenant/register', \App\Http\Controllers\Api\RegisterController::class)->name('api.tenant.register');
Route::get('/tenant/verify-email/{tenant}/{hash}', \App\Http\Controllers\Api\VerifyEmailController::class)
    ->middleware(['signed'])
    ->name('api.tenant.verification.verify');

Route::post('/tenant/reset-password', \VHAP\Core\Http\Controllers\Api\ResetTenantPasswordController::class)
    ->middleware([\Spatie\Multitenancy\Http\Middleware\NeedsTenant::class])
    ->name('api.tenant.password.reset');
