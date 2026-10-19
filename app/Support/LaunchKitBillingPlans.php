<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Server-side plan catalogue. Checkout amounts never come from request input.
 */
final class LaunchKitBillingPlans
{
    /** @var array<string, array{name: string, amount: int, currency: string, mode: string, interval?: string, description: string}> */
    public const PLANS = [
        'basic' => [
            'name' => 'LaunchKit AI Basic',
            'amount' => 9900,
            'currency' => 'usd',
            'mode' => 'subscription',
            'interval' => 'month',
            'description' => 'Basic plan billed monthly.',
        ],
        'agency' => [
            'name' => 'LaunchKit AI Agency',
            'amount' => 49900,
            'currency' => 'usd',
            'mode' => 'subscription',
            'interval' => 'month',
            'description' => 'Agency plan billed monthly.',
        ],
        'enterprise' => [
            'name' => 'LaunchKit AI Enterprise',
            'amount' => 99900,
            'currency' => 'usd',
            'mode' => 'payment',
            'description' => 'One-time Enterprise license purchase.',
        ],
    ];

    /** @return array{name: string, amount: int, currency: string, mode: string, interval?: string, description: string}|null */
    public static function find(string $key): ?array
    {
        return self::PLANS[$key] ?? null;
    }

    public static function isRecurring(string $key): bool
    {
        return (self::PLANS[$key]['mode'] ?? null) === 'subscription';
    }
}
