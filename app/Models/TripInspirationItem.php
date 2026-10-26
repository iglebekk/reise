<?php

namespace App\Models;

use Database\Factories\TripInspirationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['projection_id', 'canonical_name'])]
#[Hidden(['id', 'projection_id'])]
class TripInspirationItem extends Model
{
    /** @use HasFactory<TripInspirationItemFactory> */
    use HasFactory;

    public function projection(): BelongsTo
    {
        return $this->belongsTo(TripInspirationProjection::class, 'projection_id');
    }
}
