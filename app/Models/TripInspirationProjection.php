<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TripInspirationProjectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trip_id', 'owner_id', 'trip_type', 'transport', 'accommodation', 'duration_group'])]
#[Hidden(['id', 'trip_id', 'owner_id'])]
class TripInspirationProjection extends Model
{
    /** @use HasFactory<TripInspirationProjectionFactory> */
    use BelongsToTenant, HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TripInspirationItem::class, 'projection_id');
    }
}
