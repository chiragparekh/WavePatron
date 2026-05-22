<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->unique()->constrained()->cascadeOnDelete();
            $table->float('duration_seconds')->nullable()->index();
            $table->string('duration')->nullable();
            $table->float('start_time')->nullable();
            $table->string('container_format')->nullable();
            $table->unsignedInteger('bitrate')->nullable()->index();
            $table->string('codec')->nullable()->index();
            $table->string('codec_long_name')->nullable();
            $table->unsignedInteger('sample_rate')->nullable();
            $table->unsignedTinyInteger('channels')->nullable();
            $table->string('channel_layout')->nullable();
            $table->json('tags')->nullable();
            $table->json('cover_art')->nullable();
            $table->json('validation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_metadata');
    }
};
