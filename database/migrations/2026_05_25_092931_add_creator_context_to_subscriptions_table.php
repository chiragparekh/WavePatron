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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('creator_profile_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('tier_id')->nullable()->after('creator_profile_id')->constrained()->nullOnDelete();
            $table->string('local_status')->nullable()->after('stripe_status');

            $table->index(['user_id', 'creator_profile_id', 'stripe_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creator_profile_id');
            $table->dropConstrainedForeignId('tier_id');
            $table->dropColumn('local_status');
        });
    }
};
