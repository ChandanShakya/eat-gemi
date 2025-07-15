<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecommendController;
use App\Http\Controllers\VisitedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Restaurant recommendations
    Route::prefix('recommend')->group(function () {
        Route::get('/', [RecommendController::class, 'getRecommendations']);
        Route::post('alternatives', [RecommendController::class, 'getAlternatives']);
    });

    // Visited places management
    Route::prefix('visited')->group(function () {
        Route::get('/', [VisitedController::class, 'index']);
        Route::post('/', [VisitedController::class, 'store']);
        Route::get('{id}', [VisitedController::class, 'show']);
        Route::put('{id}', [VisitedController::class, 'update']);
        Route::delete('{id}', [VisitedController::class, 'destroy']);
        Route::get('nearby/search', [VisitedController::class, 'nearby']);
    });

    // User profile
    Route::get('user', function (Request $request) {
        return $request->user();
    });
});

// Health check route (public)
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});

// API documentation info (public)
Route::get('/', function () {
    return response()->json([
        'name' => 'MapGemi API',
        'description' => 'AI-Powered Location Finder API',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /api/auth/register' => 'User registration',
            'POST /api/auth/login' => 'User login',
            'POST /api/auth/logout' => 'User logout (authenticated)',
            'GET /api/auth/me' => 'Get current user (authenticated)',
            'GET /api/recommend?prompt=...' => 'Get location recommendations (authenticated)',
            'POST /api/recommend/alternatives' => 'Get alternative recommendations (authenticated)',
            'GET /api/visited' => 'Get visited places (authenticated)',
            'POST /api/visited' => 'Mark place as visited (authenticated)',
            'GET /api/visited/{id}' => 'Get specific visited place (authenticated)',
            'PUT /api/visited/{id}' => 'Update visited place (authenticated)',
            'DELETE /api/visited/{id}' => 'Remove visited place (authenticated)',
            'GET /api/visited/nearby/search' => 'Find nearby visited places (authenticated)',
            'GET /api/health' => 'Health check',
        ],
    ]);
});
