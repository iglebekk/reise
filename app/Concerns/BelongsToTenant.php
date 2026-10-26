<?php

namespace App\Concerns;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
