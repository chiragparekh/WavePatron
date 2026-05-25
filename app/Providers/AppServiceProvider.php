<?php

namespace App\Providers;

use App\Contracts\ChecksCreatorProfile;
use App\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\TwoFactorLoginResponse;
use App\Listeners\LogImpersonationActivity;
use App\Policies\ActivityPolicy;
use App\Support\CreatorProfile\NullCreatorProfileChecker;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Spatie\Activitylog\Models\Activity;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChecksCreatorProfile::class, NullCreatorProfileChecker::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
    }

    public function boot(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);

        Event::listen(EnterImpersonation::class, [LogImpersonationActivity::class, 'handleEnter']);
        Event::listen(LeaveImpersonation::class, [LogImpersonationActivity::class, 'handleLeave']);

        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
