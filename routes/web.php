<?php

use App\Http\Controllers\Account\UpdateAccountModeController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\Creator\AudioController as CreatorAudioController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\OnboardingController;
use App\Http\Controllers\Creator\PayoutController;
use App\Http\Controllers\Creator\ProfileController as CreatorProfileController;
use App\Http\Controllers\Creator\TierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\CreatorProfileController as PublicCreatorProfileController;
use App\Http\Controllers\Public\CreatorSubscribeController;
use App\Http\Controllers\Listener\DashboardController as ListenerDashboardController;
use App\Http\Controllers\Upload\CreateUploadController;
use App\Http\Controllers\Upload\HlsPlaylistController;
use App\Http\Controllers\Upload\HlsSegmentController;
use App\Http\Controllers\Upload\WaveformController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::stripeWebhooks('stripe/webhooks')->name('stripe.webhooks');

Route::get('creators/{handle}', PublicCreatorProfileController::class)->name('creators.show');

Route::get('creators/{profile}/subscribe/{tier}', CreatorSubscribeController::class)
    ->middleware(['auth', 'verified'])
    ->name('creators.subscribe');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('account/mode', UpdateAccountModeController::class)->name('account.mode.update');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('listener/dashboard', ListenerDashboardController::class)->name('listener.dashboard');
    Route::get('creator/dashboard', CreatorDashboardController::class)->name('creator.dashboard');
    Route::get('creator/onboarding', OnboardingController::class)->name('creator.onboarding');
    Route::get('creator/profile/edit', [CreatorProfileController::class, 'edit'])->name('creator.profile.edit');
    Route::post('creator/profile', [CreatorProfileController::class, 'store'])->name('creator.profile.store');
    Route::put('creator/profile', [CreatorProfileController::class, 'update'])->name('creator.profile.update');

    Route::middleware('role:creator')->prefix('creator')->name('creator.')->group(function () {
        Route::get('audios', [CreatorAudioController::class, 'index'])->name('audios.index');
        Route::get('audios/{upload}', [CreatorAudioController::class, 'edit'])->name('audios.edit');
        Route::put('audios/{upload}', [CreatorAudioController::class, 'update'])->name('audios.update');

        Route::get('tiers', [TierController::class, 'index'])->name('tiers.index');
        Route::get('tiers/create', [TierController::class, 'create'])->name('tiers.create');
        Route::post('tiers', [TierController::class, 'store'])->name('tiers.store');
        Route::get('tiers/{tier}', [TierController::class, 'edit'])->name('tiers.edit');
        Route::put('tiers/{tier}', [TierController::class, 'update'])->name('tiers.update');
        Route::post('tiers/{tier}/submit', [TierController::class, 'submit'])->name('tiers.submit');
        Route::post('tiers/{tier}/activate', [TierController::class, 'activate'])->name('tiers.activate');
        Route::post('tiers/{tier}/archive', [TierController::class, 'archive'])->name('tiers.archive');

        Route::get('payouts', [PayoutController::class, 'show'])->name('payouts.show');
        Route::post('payouts/onboarding', [PayoutController::class, 'store'])->name('payouts.onboarding');
    });

    Route::get('audios', [AudioController::class, 'index'])->name('audios.index');
    Route::get('uploads/create', CreateUploadController::class)->name('uploads.create');

    Route::get('uploads/{upload}/hls/playlist.m3u8', [HlsPlaylistController::class, 'show'])
        ->name('uploads.hls.playlist');
    Route::get('uploads/{upload}/hls/{segment}', [HlsSegmentController::class, 'show'])
        ->where('segment', 'segment_\d+\.ts')
        ->name('uploads.hls.segment');
    Route::get('uploads/{upload}/waveform.json', [WaveformController::class, 'show'])
        ->name('uploads.waveform');
});

require __DIR__.'/settings.php';
