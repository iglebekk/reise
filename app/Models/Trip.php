<?php

namespace App\Models;

use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'created_by_user_id',
    'destination',
    'start_date',
    'end_date',
    'type',
    'transport',
    'accommodation',
    'activities',
    'description',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'activities' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TripParticipant::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TripInvitation::class);
    }

    public function packingItems(): HasMany
    {
        return $this->hasMany(PackingItem::class);
    }

    public function inspirationProjection(): HasOne
    {
        return $this->hasOne(TripInspirationProjection::class);
    }

    public function durationInDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date, absolute: false) + 1;
    }
}
