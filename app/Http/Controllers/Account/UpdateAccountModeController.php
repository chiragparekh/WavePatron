<?php

namespace App\Http\Controllers\Account;

use App\Actions\Activity\LogAppActivity;
use App\Enums\AppMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountModeRequest;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;

class UpdateAccountModeController extends Controller
{
    public function __invoke(
        UpdateAccountModeRequest $request,
        AuthRedirect $authRedirect,
        LogAppActivity $logAppActivity,
    ): RedirectResponse {
        $user = $request->user();
        $mode = AppMode::from($request->validated('mode'));
        $previousMode = $user->activeAppMode();

        $user->update(['active_mode' => $mode]);

        $logAppActivity->execute(
            event: 'mode_switched',
            subject: $user,
            causer: $user,
            properties: [
                'from' => $previousMode->value,
                'to' => $mode->value,
            ],
            logName: 'auth',
        );

        return redirect($authRedirect->homeUrl($user->fresh()));
    }
}
