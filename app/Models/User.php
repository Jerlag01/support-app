<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'company', 'organization_id', 'email', 'phone', 'password', 'role', 'locale', 'is_active', 'signature'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasEmailAuthentication, HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, InteractsWithEmailAuthentication;

    protected $attributes = [
        'role' => 'customer',
        'locale' => 'nl',
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the user protects their account with a second step (authenticator app or e-mail code).
     */
    public function hasTwoFactor(): bool
    {
        return filled($this->app_authentication_secret) || $this->has_email_authentication;
    }

    protected static function booted(): void
    {
        // Database cascades skip model events, so delete tickets one by one to clean up their files.
        static::deleting(function (User $user): void {
            $user->tickets()->each(fn (Ticket $ticket) => $ticket->delete());
        });
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Departments an agent has access to. No departments means access to all of them.
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeStaff(Builder $query): void
    {
        $query->whereIn('role', [UserRole::Agent, UserRole::Admin])->where('is_active', true);
    }

    /**
     * Active agents who can see tickets of the given department.
     *
     * @param  Builder<User>  $query
     */
    public function scopeWithAccessToDepartment(Builder $query, ?int $departmentId): void
    {
        $query->staff()->where(fn (Builder $query) => $query
            ->where('role', UserRole::Admin)
            ->orWhereDoesntHave('departments')
            ->when($departmentId, fn (Builder $query) => $query->orWhereHas('departments', fn (Builder $query) => $query->whereKey($departmentId))));
    }

    /**
     * Admins and agents without department restrictions see every ticket.
     */
    public function hasAccessToAllDepartments(): bool
    {
        return $this->isAdmin() || ! $this->departments()->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $panel->getId() === 'admin' ? $this->isAdmin() : $this->isStaff();
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function displayName(): string
    {
        return filled($this->company) ? "{$this->name} ({$this->company})" : $this->name;
    }
}
