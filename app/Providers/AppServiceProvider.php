<?php

namespace App\Providers;

use App\Contracts\ChecksCreatorProfile;
use App\Contracts\ChecksSubscriptionAccess;
use App\Contracts\CreatesStripeTierProduct;
use App\Contracts\ManagesStripeConnect;
use App\Http\Responses\Auth\LoginResponse;
use App\Http\Responses\Auth\TwoFactorLoginResponse;
use App\Listeners\LogImpersonationActivity;
use App\Models\Subscription;
use App\Policies\ActivityPolicy;
use App\Support\CreatorProfile\CreatorProfileChecker;
use App\Support\Stripe\FakeStripeConnectService;
use App\Support\Stripe\FakeStripeTierProductCreator;
use App\Support\Stripe\StripeConnectService;
use App\Support\Subscription\SubscriptionAccessChecker;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Spatie\Activitylog\Models\Activity;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChecksCreatorProfile::class, CreatorProfileChecker::class);
        $this->app->singleton(ChecksSubscriptionAccess::class, SubscriptionAccessChecker::class);
        $this->app->singleton(ManagesStripeConnect::class, function () {
            return $this->app->environment('testing')
                ? new FakeStripeConnectService
                : new StripeConnectService;
        });
        $this->app->singleton(CreatesStripeTierProduct::class, FakeStripeTierProductCreator::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
    }

    public function boot(): void
    {
        Cashier::useSubscriptionModel(Subscription::class);

        Event::listen(EnterImpersonation::class, [LogImpersonationActivity::class, 'handleEnter']);
        Event::listen(LeaveImpersonation::class, [LogImpersonationActivity::class, 'handleLeave']);

        Gate::policy(Activity::class, ActivityPolicy::class);

        $this->ensureApplicationRolesExist();
        $this->configureDefaults();
    }

    protected function ensureApplicationRolesExist(): void
    {
        once(function (): void {
            if (! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
                return;
            }

            $this->app->make(RoleSeeder::class)->run();
        });
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
