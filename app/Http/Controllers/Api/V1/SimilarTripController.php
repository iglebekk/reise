<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimilarTripResource;
use App\Models\Trip;
use App\Services\SearchSimilarTrips;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SimilarTripController extends Controller
{
    public function index(
        Request $request,
        Trip|string $trip,
        SearchSimilarTrips $searchSimilarTrips,
    ): AnonymousResourceCollection {
        return SimilarTripResource::collection($searchSimilarTrips->handle($request->user()));
    }
}
