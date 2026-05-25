<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('gross_amount_cents');
            $table->unsignedInteger('stripe_fee_cents')->nullable();
            $table->unsignedInteger('platform_fee_cents');
            $table->unsignedInteger('creator_payout_cents');
            $table->string('currency', 3);
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['creator_profile_id', 'status']);
            $table->index(['user_id', 'creator_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_snapshots');
    }
};
