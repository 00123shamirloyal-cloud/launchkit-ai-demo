<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('last_stripe_checkout_session_id')->nullable()->unique();
            $table->string('subscription_plan', 32)->nullable();
            $table->string('subscription_status', 32)->default('inactive')->index();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('enterprise_purchased_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'last_stripe_checkout_session_id',
                'subscription_plan',
                'subscription_status',
                'subscription_ends_at',
                'enterprise_purchased_at',
            ]);
        });
    }
};
