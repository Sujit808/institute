<?php

use App\Http\Controllers\AttendanceIntegrationController;
use App\Http\Controllers\BillingWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/attendance/punch', [AttendanceIntegrationController::class, 'punch'])
    ->middleware('throttle:120,1')
    ->name('api.attendance.punch');

Route::post('/billing/webhook', BillingWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('api.billing.webhook');
