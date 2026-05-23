<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\Upload\HlsPlaylistController;
use App\Http\Controllers\Upload\HlsSegmentController;
use App\Http\Controllers\Upload\WaveformController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('audios', [AudioController::class, 'index'])->name('audios.index');

    Route::get('uploads/{upload}/hls/playlist.m3u8', [HlsPlaylistController::class, 'show'])
        ->name('uploads.hls.playlist');
    Route::get('uploads/{upload}/hls/{segment}', [HlsSegmentController::class, 'show'])
        ->where('segment', 'segment_\d+\.ts')
        ->name('uploads.hls.segment');
    Route::get('uploads/{upload}/waveform.json', [WaveformController::class, 'show'])
        ->name('uploads.waveform');
});

require __DIR__.'/settings.php';
