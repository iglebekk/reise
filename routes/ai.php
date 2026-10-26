<?php

use App\Mcp\Servers\TripServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::middleware('throttle:mcp')->group(function (): void {
    Mcp::oauthRoutes();
});

Mcp::web('/mcp/trips', TripServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
