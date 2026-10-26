<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreatePackingItemTool;
use App\Mcp\Tools\CreateTripTool;
use App\Mcp\Tools\DeletePackingItemTool;
use App\Mcp\Tools\ListTripsTool;
use App\Mcp\Tools\SearchSimilarTripsTool;
use App\Mcp\Tools\ShowPackingListsTool;
use App\Mcp\Tools\ShowTripTool;
use App\Mcp\Tools\UpdatePackingItemTool;
use App\Mcp\Tools\UpdateTripTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Trip Planner')]
#[Version('0.1.0')]
#[Instructions('Manage trips and packing lists for the authenticated user.')]
class TripServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        CreateTripTool::class,
        CreatePackingItemTool::class,
        UpdatePackingItemTool::class,
        DeletePackingItemTool::class,
        ListTripsTool::class,
        SearchSimilarTripsTool::class,
        ShowTripTool::class,
        ShowPackingListsTool::class,
        UpdateTripTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [];
}
