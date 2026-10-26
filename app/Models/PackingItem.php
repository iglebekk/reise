<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\PackingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trip_id', 'participant_id', 'name', 'quantity', 'comment'])]
class PackingItem extends Model
{
    /** @use HasFactory<PackingItemFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TripParticipant::class, 'participant_id');
    }
}
