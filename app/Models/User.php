<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class)->withPivot('role')->withTimestamps();
    }

    public function createdTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'created_by_user_id');
    }

    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'current_tenant_id');
    }

    public function setCurrentTenant(Trip $trip): void
    {
        $this->setRelation('currentTenant', $trip);
    }

    /**
     * Expose the selected trip ID without persisting request-local tenant state.
     *
     * @return Attribute<int|null, never>
     */
    protected function currentTenantId(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->relationLoaded('currentTenant')
            ? $this->getRelation('currentTenant')?->getKey()
            : null);
    }

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
        ];
    }
}
