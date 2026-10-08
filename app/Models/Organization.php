<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

#[Fillable(['name', 'domain', 'phone', 'address', 'notes'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Organization $organization): void {
            $organization->domain = filled($organization->domain)
                ? Str::of($organization->domain)->lower()->trim()->ltrim('@')->toString()
                : null;
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasManyThrough<Ticket, User, $this>
     */
    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, User::class);
    }

    /**
     * Find the organization a new client belongs to by the domain of their e-mail address.
     */
    public static function matchingEmail(string $email): ?self
    {
        $domain = Str::of($email)->after('@')->lower()->toString();

        return $domain === '' ? null : static::where('domain', $domain)->first();
    }
}
