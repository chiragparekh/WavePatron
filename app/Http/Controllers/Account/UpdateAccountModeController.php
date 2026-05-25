<?php

namespace App\Http\Controllers\Account;

use App\Enums\AppMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountModeRequest;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;

class UpdateAccountModeController extends Controller
{
    public function __invoke(UpdateAccountModeRequest $request, AuthRedirect $authRedirect): RedirectResponse
    {
        $user = $request->user();
        $mode = AppMode::from($request->validated('mode'));

        $user->update(['active_mode' => $mode]);

        return redirect($authRedirect->homeUrl($user->fresh()));
    }
}
