<?php

namespace App\Http\Responses\Auth;

use App\Actions\Activity\LogAppActivity;
use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function __construct(
        private AuthRedirect $authRedirect,
        private LogAppActivity $logAppActivity,
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $redirectTo = $this->authRedirect->homeUrl($user);

        $this->logAppActivity->execute(
            event: 'auth_redirect',
            subject: $user,
            causer: $user,
            properties: [
                'redirect_to' => $redirectTo,
                'two_factor' => true,
            ],
            logName: 'auth',
        );

        return redirect()->intended($redirectTo);
    }
}
