<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendController extends Controller
{
    /**
     * Get restaurant recommendations using Gemini AI
     */
    public function getRecommendations(Request $request)
    {
        $request->validate([
            'city' => 'required|string|max:255',
        ]);

        $city = $request->query('city');
        $geminiApiKey = config('services.gemini.api_key');

        if (! $geminiApiKey) {
            return response()->json([
                'message' => 'Gemini API key not configured',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Construct prompt for Gemini API
            $prompt = "Suggest 5 restaurants in {$city} with menu table or image links. For each restaurant, provide: name, address, cuisine type, price range, and if possible a menu link or image URL. Format the response as JSON with an array of restaurants.";

            // Call Gemini API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'message' => 'Failed to get recommendations. Please try again later.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $data = $response->json();

            // Extract the generated text
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Try to parse JSON from the response
            $restaurants = $this->parseRestaurantsFromResponse($generatedText);

            return response()->json([
                'message' => 'Recommendations retrieved successfully',
                'city' => $city,
                'restaurants' => $restaurants,
                'raw_response' => $generatedText,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting recommendations', [
                'error' => $e->getMessage(),
                'city' => $city,
            ]);

            // Return mock data for development/testing
            $mockRestaurants = [
                [
                    'name' => 'Sushi Zen',
                    'address' => '123 Main St, ' . $city,
                    'cuisine' => 'Japanese',
                    'price_range' => '$$$',
                    'menu_url' => 'https://example.com/sushi-zen-menu',
                    'description' => 'Authentic sushi experience with fresh ingredients',
                ],
                [
                    'name' => 'Pasta Palace',
                    'address' => '456 Food Ave, ' . $city,
                    'cuisine' => 'Italian',
                    'price_range' => '$$',
                    'menu_url' => 'https://example.com/pasta-palace-menu',
                    'description' => 'Traditional Italian pasta dishes',
                ],
                [
                    'name' => 'Burger Bliss',
                    'address' => '789 Taste Blvd, ' . $city,
                    'cuisine' => 'American',
                    'price_range' => '$',
                    'menu_url' => 'https://example.com/burger-bliss-menu',
                    'description' => 'Gourmet burgers and craft beer',
                ],
            ];

            return response()->json([
                'message' => 'Recommendations retrieved successfully (mock data)',
                'city' => $city,
                'restaurants' => $mockRestaurants,
                'note' => 'Using mock data - Gemini API temporarily unavailable',
            ]);
        }
    }

    /**
     * Parse restaurant data from Gemini response
     */
    private function parseRestaurantsFromResponse(string $response): array
    {
        // First, try to extract JSON from markdown code blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['restaurants']) && is_array($data['restaurants'])) {
                return $data['restaurants'];
            }
        }
        
        // Try to extract JSON array directly from the response
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $jsonString = $matches[0];
            $restaurants = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($restaurants)) {
                return $restaurants;
            }
        }
        
        // Try to extract any JSON object that contains restaurants
        if (preg_match('/\{[^}]*"restaurants"\s*:\s*\[[^\]]*\][^}]*\}/s', $response, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['restaurants']) && is_array($data['restaurants'])) {
                return $data['restaurants'];
            }
        }

        // Fallback: create structured data from text
        $lines = explode("\n", $response);
        $restaurants = [];
        $currentRestaurant = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Try to identify restaurant entries
            if (preg_match('/^\d+\.?\s*(.+)/', $line, $matches)) {
                if (! empty($currentRestaurant)) {
                    $restaurants[] = $currentRestaurant;
                }
                $currentRestaurant = [
                    'name' => trim($matches[1]),
                    'address' => '',
                    'cuisine' => '',
                    'price_range' => '',
                    'menu_url' => '',
                ];
            } elseif (! empty($currentRestaurant)) {
                // Try to categorize the line
                if (strpos(strtolower($line), 'address') !== false ||
                    strpos(strtolower($line), 'location') !== false) {
                    $currentRestaurant['address'] = $line;
                } elseif (strpos(strtolower($line), 'cuisine') !== false) {
                    $currentRestaurant['cuisine'] = $line;
                } elseif (strpos(strtolower($line), 'price') !== false ||
                         strpos($line, '$') !== false) {
                    $currentRestaurant['price_range'] = $line;
                } elseif (strpos(strtolower($line), 'menu') !== false ||
                         strpos(strtolower($line), 'http') !== false) {
                    $currentRestaurant['menu_url'] = $line;
                }
            }
        }

        if (! empty($currentRestaurant)) {
            $restaurants[] = $currentRestaurant;
        }

        return $restaurants;
    }

    /**
     * Get alternative recommendations after visiting a place
     */
    public function getAlternatives(Request $request)
    {
        $request->validate([
            'visited_place_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ]);

        $visitedPlace = $request->input('visited_place_name');
        $city = $request->input('city');
        $geminiApiKey = config('services.gemini.api_key');

        try {
            $prompt = "I just visited {$visitedPlace} in {$city}. Suggest 2-3 alternative restaurants in the same city with similar cuisine or atmosphere. For each restaurant, provide: name, address, cuisine type, what makes it special, and if possible a menu link.";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Failed to get alternative recommendations',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $data = $response->json();
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $alternatives = $this->parseRestaurantsFromResponse($generatedText);

            return response()->json([
                'message' => 'Alternative recommendations retrieved successfully',
                'visited_place' => $visitedPlace,
                'city' => $city,
                'alternatives' => $alternatives,
                'raw_response' => $generatedText,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting alternatives', [
                'error' => $e->getMessage(),
                'visited_place' => $visitedPlace,
                'city' => $city,
            ]);

            return response()->json([
                'message' => 'An error occurred while getting alternatives',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
