<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TripInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['trip_id', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at'])]
#[Hidden(['token_hash'])]
class TripInvitation extends Model
{
    /** @use HasFactory<TripInvitationFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
