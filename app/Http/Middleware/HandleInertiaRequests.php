<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use STS\FilamentImpersonate\Facades\Impersonation;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'appMode' => $user ? [
                'active' => $user->activeAppMode()->value,
                'available' => array_map(
                    fn ($mode) => $mode->value,
                    $user->availableAppModes(),
                ),
                'canSwitch' => $user->canSwitchAppMode(),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'impersonation' => $this->impersonationState($user),
        ];
    }

    /**
     * @return array{active: true, user: array{name: string, email: string}, leaveUrl: string}|null
     */
    private function impersonationState(?User $user): ?array
    {
        if ($user === null || ! Impersonation::isImpersonating()) {
            return null;
        }

        return [
            'active' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'leaveUrl' => route('filament-impersonate.leave'),
        ];
    }
}
