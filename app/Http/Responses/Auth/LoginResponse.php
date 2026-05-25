<?php

namespace App\Http\Responses\Auth;

use App\Actions\Activity\LogAppActivity;
use App\Support\AuthRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
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
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        $redirectTo = $this->authRedirect->homeUrl($user);

        $this->logAppActivity->execute(
            event: 'auth_redirect',
            subject: $user,
            causer: $user,
            properties: ['redirect_to' => $redirectTo],
            logName: 'auth',
        );

        return redirect()->intended($redirectTo);
    }
}
