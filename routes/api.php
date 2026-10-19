<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Laravel's API middleware group is stateless and does not require a browser CSRF token.
// URL: POST /api/stripe/webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);