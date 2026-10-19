<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LaunchKitBillingPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class StripeCheckoutController extends Controller
{
    /**
     * Create a hosted Stripe Checkout Session for a server-defined plan.
     * Protect this route with auth middleware; never accept prices from the browser.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys(LaunchKitBillingPlans::PLANS))],
        ]);

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $planKey = (string) $validated['plan'];
        $plan = LaunchKitBillingPlans::find($planKey);

        if ($plan === null) {
            return back()->withErrors(['plan' => 'The selected plan is not available.']);
        }

        // Avoid unintentionally starting a second paid plan/subscription for the same account.
        if ($user->subscription_status === 'active') {
            return back()->withErrors([
                'plan' => 'Your account already has an active plan. Contact support to change plans.',
            ]);
        }

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            Log::critical('Stripe secret key is not configured.');

            return back()->withErrors([
                'billing' => 'Checkout is temporarily unavailable. Please try again later.',
            ]);
        }

        try {
            $stripe = new StripeClient($secret);

            $metadata = [
                'user_id' => (string) $user->getKey(),
                'plan' => $planKey,
                'billing_type' => $plan['mode'],
                'integration' => 'launchkit_ai',
            ];

            $lineItem = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => $plan['currency'],
                    'unit_amount' => $plan['amount'],
                    'product_data' => [
                        'name' => $plan['name'],
                        'description' => $plan['description'],
                        'metadata' => [
                            'launchkit_plan' => $planKey,
                        ],
                    ],
                ],
            ];

            if ($plan['mode'] === 'subscription') {
                $lineItem['price_data']['recurring'] = [
                    'interval' => (string) $plan['interval'],
                ];
            }

            $parameters = [
                'mode' => $plan['mode'],
                'line_items' => [$lineItem],
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.cancel'),
                'client_reference_id' => (string) $user->getKey(),
                'metadata' => $metadata,
                'billing_address_collection' => 'auto',
                'allow_promotion_codes' => false,
            ];

            if (filled($user->stripe_customer_id)) {
                $parameters['customer'] = $user->stripe_customer_id;
            } else {
                $parameters['customer_email'] = $user->email;
            }

            if ($plan['mode'] === 'subscription') {
                // The metadata on the Subscription is also present for invoice webhooks.
                $parameters['subscription_data'] = [
                    'metadata' => $metadata,
                ];
            } else {
                // Copies metadata onto the payment for reconciliation of one-time purchases.
                $parameters['payment_intent_data'] = [
                    'metadata' => $metadata,
                ];
            }

            /** @var Session $session */
            $session = $stripe->checkout->sessions->create(
                $parameters,
                ['idempotency_key' => 'launchkit-checkout-' . $user->getKey() . '-' . Str::uuid()],
            );

            if (! is_string($session->url) || $session->url === '') {
                Log::error('Stripe returned a Checkout Session without a redirect URL.', [
                    'user_id' => $user->getKey(),
                    'session_id' => $session->id,
                ]);

                return back()->withErrors([
                    'billing' => 'Checkout could not be opened. Please try again.',
                ]);
            }

            return redirect()->away($session->url);
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe rejected a Checkout Session request.', [
                'user_id' => $user->getKey(),
                'plan' => $planKey,
                'stripe_error_type' => $exception::class,
                'stripe_error_code' => $exception->getStripeCode(),
            ]);

            return back()->withErrors([
                'billing' => 'Stripe could not start checkout. Please try again later.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Unexpected error while creating Stripe Checkout Session.', [
                'user_id' => $user->getKey(),
                'plan' => $planKey,
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'billing' => 'Checkout is temporarily unavailable. Please try again later.',
            ]);
        }
    }
}
