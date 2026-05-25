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
        Schema::create('creator_fee_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('percentage_fee', 5, 2);
            $table->unsignedInteger('fixed_fee_cents')->default(0);
            $table->string('currency', 3)->default('usd');
            $table->timestamp('effective_at');
            $table->timestamps();

            $table->index(['creator_profile_id', 'effective_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_fee_overrides');
    }
};
