<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LaunchKitBillingPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeObject;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    /**
     * Receive Stripe events. This route is intentionally unauthenticated; Stripe's
     * signed payload is the authentication mechanism. Keep it outside CSRF-protected
     * web routes (routes/api.php is recommended).
     */
    public function handle(Request $request): JsonResponse
    {
        $webhookSecret = (string) config('services.stripe.webhook_secret');

        if ($webhookSecret === '') {
            Log::critical('Stripe webhook secret is not configured.');

            return response()->json(['message' => 'Webhook is not configured.'], 500);
        }

        $signature = (string) $request->header('Stripe-Signature', '');

        if ($signature === '') {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        try {
            // Verify the signature against the exact raw request body before parsing/trusting it.
            $event = Webhook::constructEvent($request->getContent(), $signature, $webhookSecret);
        } catch (UnexpectedValueException | SignatureVerificationException $exception) {
            Log::warning('Rejected Stripe webhook with an invalid payload or signature.', [
                'exception' => $exception::class,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid webhook signature or payload.'], 400);
        } catch (Throwable $exception) {
            Log::error('Stripe webhook verification failed unexpectedly.', [
                'exception' => $exception::class,
            ]);

            return response()->json(['message' => 'Unable to verify webhook.'], 400);
        }

        $eventId = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? '');

        if ($eventId === '' || $eventType === '') {
            return response()->json(['message' => 'Malformed Stripe event.'], 400);
        }

        try {
            // Insert once outside the processing transaction, so a failed attempt can
            // be recorded and safely retried by Stripe. The unique ID makes delivery idempotent.
            DB::table('stripe_webhook_events')->insertOrIgnore([
                'stripe_event_id' => $eventId,
                'type' => $eventType,
                'status' => 'received',
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $alreadyProcessed = DB::transaction(function () use ($event, $eventId, $eventType): bool {
                $receipt = DB::table('stripe_webhook_events')
                    ->where('stripe_event_id', $eventId)
                    ->lockForUpdate()
                    ->first();

                if ($receipt === null) {
                    throw new \RuntimeException('Webhook receipt row was not created.');
                }

                if ($receipt->status === 'processed') {
                    return true;
                }

                DB::table('stripe_webhook_events')
                    ->where('stripe_event_id', $eventId)
                    ->update([
                        'status' => 'processing',
                        'attempts' => DB::raw('attempts + 1'),
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);

                $this->dispatchEvent($eventType, $event->data->object ?? null);

                DB::table('stripe_webhook_events')
                    ->where('stripe_event_id', $eventId)
                    ->update([
                        'status' => 'processed',
                        'processed_at' => now(),
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);

                return false;
            });

            if ($alreadyProcessed) {
                Log::info('Ignored duplicate Stripe webhook delivery.', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);
            }

            return response()->json(['received' => true], 200);
        } catch (Throwable $exception) {
            // The business transaction is rolled back on failure; persist the failure
            // outside it so support staff can inspect it and Stripe can retry with 5xx.
            try {
                DB::table('stripe_webhook_events')
                    ->where('stripe_event_id', $eventId)
                    ->update([
                        'status' => 'failed',
                        'attempts' => DB::raw('attempts + 1'),
                        'last_error' => mb_substr($exception->getMessage(), 0, 60000),
                        'updated_at' => now(),
                    ]);
            } catch (Throwable $receiptException) {
                Log::critical('Could not save Stripe webhook failure state.', [
                    'event_id' => $eventId,
                    'exception' => $receiptException::class,
                ]);
            }

            Log::error('Stripe webhook processing failed.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'exception' => $exception::class,
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }
    }

    private function dispatchEvent(string $eventType, mixed $object): void
    {
        if (! $object instanceof StripeObject) {
            // Unknown events can be acknowledged, but the events we handle must contain an object.
            if (in_array($eventType, [
                'checkout.session.completed',
                'checkout.session.async_payment_succeeded',
                'invoice.payment_succeeded',
                'invoice.payment_failed',
                'customer.subscription.deleted',
            ], true)) {
                throw new \UnexpectedValueException('Stripe event object was missing or invalid.');
            }

            return;
        }

        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutSession($object, false),
            'checkout.session.async_payment_succeeded' => $this->handleCheckoutSession($object, true),
            'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            default => null, // Acknowledge event types that this integration does not consume.
        };
    }

    private function handleCheckoutSession(StripeObject $session, bool $asyncPaymentSucceeded): void
    {
        $metadata = $session->metadata ?? null;
        $userId = $this->metadataValue($metadata, 'user_id') ?? (string) ($session->client_reference_id ?? '');
        $planKey = $this->metadataValue($metadata, 'plan') ?? '';
        $plan = LaunchKitBillingPlans::find($planKey);

        if ($userId === '' || ! ctype_digit($userId) || $plan === null) {
            throw new \UnexpectedValueException('Checkout Session does not contain a valid user and plan.');
        }

        $user = User::query()->whereKey((int) $userId)->lockForUpdate()->first();

        if ($user === null) {
            // A customer account may have been deleted after checkout. A retry cannot recreate it.
            Log::warning('Stripe Checkout Session belongs to a missing user.', [
                'stripe_session_id' => $session->id ?? null,
                'user_id' => $userId,
            ]);

            return;
        }

        $mode = (string) ($session->mode ?? '');

        if ($mode !== $plan['mode']) {
            throw new \UnexpectedValueException('Stripe Checkout mode does not match the configured plan.');
        }

        $sessionId = (string) ($session->id ?? '');
        $customerId = $this->stripeId($session->customer ?? null);
        $subscriptionId = $this->stripeId($session->subscription ?? null);
        $paymentStatus = (string) ($session->payment_status ?? '');
        $paid = $asyncPaymentSucceeded || in_array($paymentStatus, ['paid', 'no_payment_required'], true);

        // Stripe does not guarantee webhook ordering. Do not let an older or
        // unrelated completed Checkout Session replace a currently active plan.
        if ($user->subscription_status === 'active') {
            $sameActiveSubscription = LaunchKitBillingPlans::isRecurring($planKey)
                && $user->subscription_plan === $planKey
                && ($user->stripe_subscription_id === null || $user->stripe_subscription_id === $subscriptionId);
            $sameEnterpriseSession = $planKey === 'enterprise'
                && $user->subscription_plan === 'enterprise'
                && $sessionId !== ''
                && $user->last_stripe_checkout_session_id === $sessionId;

            if (! $sameActiveSubscription && ! $sameEnterpriseSession) {
                Log::warning('Ignored a stale or conflicting completed Checkout Session.', [
                    'user_id' => $user->getKey(),
                    'stripe_session_id' => $sessionId,
                    'plan' => $planKey,
                    'current_plan' => $user->subscription_plan,
                ]);

                return;
            }
        }

        if ($customerId !== null) {
            $user->stripe_customer_id = $customerId;
        }

        if ($sessionId !== '') {
            $user->last_stripe_checkout_session_id = $sessionId;
        }

        if ($plan['mode'] === 'subscription') {
            if ($subscriptionId !== null) {
                $user->stripe_subscription_id = $subscriptionId;
            }

            $user->subscription_plan = $planKey;
            $user->enterprise_purchased_at = null;

            // Don't downgrade an already-activated subscription if Stripe events arrive out of order.
            if ($paid) {
                $user->subscription_status = 'active';
            } elseif ($user->subscription_status !== 'active') {
                $user->subscription_status = 'pending';
            }
        } else {
            $user->subscription_plan = $planKey;
            // Enterprise is a one-time license, not a subscription. Clear any old,
            // already-canceled subscription ID so a late deletion event cannot cancel it.
            $user->stripe_subscription_id = null;

            if ($paid) {
                $user->subscription_status = 'active';
                $user->subscription_ends_at = null;
                $user->enterprise_purchased_at ??= now();
            } elseif ($user->subscription_status !== 'active') {
                $user->subscription_status = 'pending';
            }
        }

        $user->save();
    }

    private function handleInvoicePaymentSucceeded(StripeObject $invoice): void
    {
        $customerId = $this->stripeId($invoice->customer ?? null);
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        $metadata = $this->invoiceSubscriptionMetadata($invoice);
        $userId = $this->metadataValue($metadata, 'user_id');
        $planFromMetadata = $this->metadataValue($metadata, 'plan');

        $user = null;

        if ($userId !== null && ctype_digit($userId)) {
            $user = User::query()->whereKey((int) $userId)->lockForUpdate()->first();
        }

        if ($user === null && $subscriptionId !== null) {
            $user = User::query()
                ->where('stripe_subscription_id', $subscriptionId)
                ->lockForUpdate()
                ->first();
        }

        if ($user === null && $customerId !== null) {
            $user = User::query()
                ->where('stripe_customer_id', $customerId)
                ->lockForUpdate()
                ->first();
        }

        if ($user === null) {
            // Checkout Session metadata normally resolves the user, including if event order varies.
            Log::warning('Could not match successful Stripe invoice to a LaunchKit user.', [
                'stripe_invoice_id' => $invoice->id ?? null,
                'stripe_customer_id' => $customerId,
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        // Do not let an older recurring invoice re-enable or overwrite a newer
        // Enterprise license or replace a different currently-active subscription.
        if ($user->subscription_status === 'active' && $user->subscription_plan === 'enterprise') {
            Log::warning('Ignored recurring invoice for a user with an active Enterprise license.', [
                'stripe_invoice_id' => $invoice->id ?? null,
                'user_id' => $user->getKey(),
            ]);

            return;
        }

        if (
            $user->subscription_status === 'active'
            && $subscriptionId !== null
            && $user->stripe_subscription_id !== null
            && $user->stripe_subscription_id !== $subscriptionId
        ) {
            Log::warning('Ignored an invoice for a different, non-current subscription.', [
                'stripe_invoice_id' => $invoice->id ?? null,
                'user_id' => $user->getKey(),
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        $planKey = $planFromMetadata ?? (string) ($user->subscription_plan ?? '');

        if (! LaunchKitBillingPlans::isRecurring($planKey)) {
            // This handler is for recurring subscription invoices, not the Enterprise one-time purchase.
            Log::warning('Ignoring Stripe invoice with no valid recurring plan metadata.', [
                'stripe_invoice_id' => $invoice->id ?? null,
                'user_id' => $user->getKey(),
                'plan' => $planKey,
            ]);

            return;
        }

        if ($customerId !== null) {
            $user->stripe_customer_id = $customerId;
        }

        if ($subscriptionId !== null) {
            $user->stripe_subscription_id = $subscriptionId;
        }

        $user->subscription_plan = $planKey;
        $user->subscription_status = 'active';
        $user->subscription_ends_at = null;
        $user->enterprise_purchased_at = null;
        $user->save();
    }

    private function handleInvoicePaymentFailed(StripeObject $invoice): void
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        $customerId = $this->stripeId($invoice->customer ?? null);

        $user = null;

        if ($subscriptionId !== null) {
            $user = User::query()
                ->where('stripe_subscription_id', $subscriptionId)
                ->lockForUpdate()
                ->first();
        }

        if ($user === null && $customerId !== null) {
            $user = User::query()
                ->where('stripe_customer_id', $customerId)
                ->whereIn('subscription_plan', ['basic', 'agency'])
                ->lockForUpdate()
                ->first();
        }

        if ($user === null || ! LaunchKitBillingPlans::isRecurring((string) $user->subscription_plan)) {
            Log::warning('Could not match failed Stripe invoice to a recurring LaunchKit subscription.', [
                'stripe_invoice_id' => $invoice->id ?? null,
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        $user->subscription_status = 'past_due';
        $user->save();
    }

    private function handleSubscriptionDeleted(StripeObject $subscription): void
    {
        $subscriptionId = $this->stripeId($subscription->id ?? null);

        if ($subscriptionId === null) {
            return;
        }

        $user = User::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->lockForUpdate()
            ->first();

        if ($user === null) {
            Log::warning('Deleted Stripe subscription has no matching LaunchKit user.', [
                'stripe_subscription_id' => $subscriptionId,
            ]);

            return;
        }

        // Do not cancel a newer subscription if a stale event for an older subscription arrives.
        if ($user->stripe_subscription_id !== $subscriptionId) {
            return;
        }

        $user->subscription_status = 'canceled';
        $user->subscription_ends_at = now();
        $user->save();
    }

    private function invoiceSubscriptionId(StripeObject $invoice): ?string
    {
        $direct = $this->stripeId($invoice->subscription ?? null);

        if ($direct !== null) {
            return $direct;
        }

        $parent = $invoice->parent ?? null;
        $details = $parent instanceof StripeObject ? ($parent->subscription_details ?? null) : null;

        return $details instanceof StripeObject
            ? $this->stripeId($details->subscription ?? null)
            : null;
    }

    private function invoiceSubscriptionMetadata(StripeObject $invoice): mixed
    {
        $parent = $invoice->parent ?? null;
        $details = $parent instanceof StripeObject ? ($parent->subscription_details ?? null) : null;

        if ($details instanceof StripeObject && ($details->metadata ?? null) !== null) {
            return $details->metadata;
        }

        $legacyDetails = $invoice->subscription_details ?? null;

        if ($legacyDetails instanceof StripeObject && ($legacyDetails->metadata ?? null) !== null) {
            return $legacyDetails->metadata;
        }

        return $invoice->metadata ?? null;
    }

    private function metadataValue(mixed $metadata, string $key): ?string
    {
        if (is_array($metadata)) {
            $value = $metadata[$key] ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        if ($metadata instanceof \ArrayAccess) {
            $value = $metadata->offsetExists($key) ? $metadata[$key] : null;

            return is_scalar($value) ? (string) $value : null;
        }

        if (is_object($metadata)) {
            $value = $metadata->{$key} ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    private function stripeId(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        if ($value instanceof StripeObject) {
            $id = $value->id ?? null;

            return is_string($id) && $id !== '' ? $id : null;
        }

        if (is_object($value)) {
            $id = $value->id ?? null;

            return is_string($id) && $id !== '' ? $id : null;
        }

        return null;
    }
}
