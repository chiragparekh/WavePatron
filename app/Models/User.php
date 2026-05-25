<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AppMode;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'active_mode'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active_mode' => AppMode::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Upload, $this>
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
          && $this->hasRole(Role::Admin->value);
    }

    public function canImpersonate(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function activeAppMode(): AppMode
    {
        if ($this->hasRole(Role::Creator->value) && ! $this->hasRole(Role::Listener->value)) {
            return AppMode::Creator;
        }

        if ($this->hasRole(Role::Listener->value) && ! $this->hasRole(Role::Creator->value)) {
            return AppMode::Listener;
        }

        return $this->active_mode ?? AppMode::Listener;
    }

    /**
     * @return list<AppMode>
     */
    public function availableAppModes(): array
    {
        $modes = [];

        if ($this->hasRole(Role::Listener->value)) {
            $modes[] = AppMode::Listener;
        }

        if ($this->hasRole(Role::Creator->value)) {
            $modes[] = AppMode::Creator;
        }

        return $modes;
    }

    public function canSwitchAppMode(): bool
    {
        return count($this->availableAppModes()) > 1;
    }
}
