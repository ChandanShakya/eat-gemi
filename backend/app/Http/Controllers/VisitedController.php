<?php

namespace App\Http\Controllers;

use App\Models\VisitedPlace;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class VisitedController extends Controller
{
    /**
     * Get all visited places for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $visitedPlaces = VisitedPlace::where('user_id', $user->id)
            ->orderBy('visited_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Visited places retrieved successfully',
            'data' => $visitedPlaces,
        ]);
    }

    /**
     * Store a new visited place
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'menu_image_url' => 'nullable|url',
            'menu_table' => 'nullable|array',
            'visited_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        // Check if place is already visited by this user
        $existingVisit = VisitedPlace::where('user_id', $user->id)
            ->where('place_id', $request->place_id)
            ->first();

        if ($existingVisit) {
            return response()->json([
                'message' => 'Place already marked as visited',
                'data' => $existingVisit,
            ], Response::HTTP_CONFLICT);
        }

        $visitedPlace = VisitedPlace::create([
            'user_id' => $user->id,
            'place_id' => $request->place_id,
            'name' => $request->name,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'menu_image_url' => $request->menu_image_url,
            'menu_table' => $request->menu_table,
            'visited_at' => $request->visited_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Place marked as visited successfully',
            'data' => $visitedPlace,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get a specific visited place
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $visitedPlace = VisitedPlace::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $visitedPlace) {
            return response()->json([
                'message' => 'Visited place not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Visited place retrieved successfully',
            'data' => $visitedPlace,
        ]);
    }

    /**
     * Update a visited place
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'lat' => 'sometimes|required|numeric|between:-90,90',
            'lng' => 'sometimes|required|numeric|between:-180,180',
            'menu_image_url' => 'sometimes|nullable|url',
            'menu_table' => 'sometimes|nullable|array',
            'visited_at' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();

        $visitedPlace = VisitedPlace::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $visitedPlace) {
            return response()->json([
                'message' => 'Visited place not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $visitedPlace->update($request->only([
            'name', 'lat', 'lng', 'menu_image_url', 'menu_table', 'visited_at',
        ]));

        return response()->json([
            'message' => 'Visited place updated successfully',
            'data' => $visitedPlace->fresh(),
        ]);
    }

    /**
     * Remove a visited place
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $visitedPlace = VisitedPlace::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $visitedPlace) {
            return response()->json([
                'message' => 'Visited place not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $visitedPlace->delete();

        return response()->json([
            'message' => 'Visited place removed successfully',
        ]);
    }

    /**
     * Get visited places by location (nearby)
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100', // radius in km
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5; // default 5km

        // Use Haversine formula to find nearby places
        $visitedPlaces = VisitedPlace::where('user_id', $user->id)
            ->selectRaw('
                *,
                (6371 * acos(cos(radians(?)) 
                * cos(radians(lat)) 
                * cos(radians(lng) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(lat)))) AS distance
            ', [$lat, $lng, $lat])
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json([
            'message' => 'Nearby visited places retrieved successfully',
            'data' => $visitedPlaces,
            'center' => ['lat' => $lat, 'lng' => $lng],
            'radius_km' => $radius,
        ]);
    }
}
