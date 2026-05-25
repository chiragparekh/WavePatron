<?php

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
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
        Schema::table('uploads', function (Blueprint $table) {
            $table->string('title')->nullable()->after('original_name');
            $table->text('description')->nullable()->after('title');
            $table->string('publish_status')->default(AudioPublishStatus::Draft->value)->after('status');
            $table->string('access_level')->default(AudioAccessLevel::Free->value)->after('publish_status');

            $table->index(['publish_status', 'access_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropIndex(['publish_status', 'access_level']);
            $table->dropColumn(['title', 'description', 'publish_status', 'access_level']);
        });
    }
};
