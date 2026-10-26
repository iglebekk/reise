<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TripParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['trip_id', 'user_id', 'name', 'age'])]
class TripParticipant extends Model
{
    /** @use HasFactory<TripParticipantFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function packingItems(): HasMany
    {
        return $this->hasMany(PackingItem::class, 'participant_id');
    }
}
